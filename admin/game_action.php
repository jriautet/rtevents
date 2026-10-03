<?php
require_once __DIR__.'/../config/config.php'; require_admin();
$id=(int)($_POST['id']??0); $action=$_POST['action']??''; $pdo=db();
$s=$pdo->prepare("SELECT g.*,s.slug FROM games g JOIN stories s ON s.id=g.story_id WHERE g.id=?");$s->execute([$id]);$g=$s->fetch(PDO::FETCH_ASSOC);if(!$g) redirect('/admin/index.php');
$now=iso_now(); $stageSecs=stage_duration_seconds($g);
if($action==='start'){
    if(!all_players_ready($id)){ flash('Impossible de démarrer : chaque joueur doit avoir un rôle attribué et avoir confirmé sa position.'); redirect('/admin/game.php?id='.$id); }
    $status=$g['status']==='paused'?'running':'running';
    if(empty($g['started_at'])){
        $pdo->prepare("UPDATE games SET status='running',current_stage=CASE WHEN current_stage=0 THEN 1 ELSE current_stage END,started_at=?,ends_at=datetime(?,'+'||duration||' minutes'),stage_started_at=?,stage_ends_at=datetime(?,'+'||?||' seconds'),response_required=0,response_value=NULL WHERE id=?")
            ->execute([$now,$now,$now,$now,$stageSecs,$id]);
    } else {
        $pdo->prepare("UPDATE games SET status=?, response_required=0 WHERE id=?")->execute([$status,$id]);
    }
    log_game($id,'game_started');
}elseif($action==='pause'){
    $pdo->prepare("UPDATE games SET status='paused' WHERE id=?")->execute([$id]); log_game($id,'game_paused');
}elseif($action==='resume'){
    $pdo->prepare("UPDATE games SET status='running' WHERE id=?")->execute([$id]); log_game($id,'game_resumed');
}elseif($action==='reset'){
    $pdo->prepare("UPDATE games SET status='waiting',current_stage=0,started_at=NULL,ends_at=NULL,stage_started_at=NULL,stage_ends_at=NULL,response_required=0,response_value=NULL,completed_at=NULL WHERE id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM game_state WHERE game_id=?")->execute([$id]);$pdo->prepare("DELETE FROM discoveries WHERE game_id=?")->execute([$id]);$pdo->prepare("UPDATE players SET ready=0 WHERE game_id=?")->execute([$id]);log_game($id,'game_reset');
}elseif($action==='stage'){
    $stage=max(0,min(5,(int)($_POST['stage']??0)));
    $stageStart=$stage>0?$now:null; $stageEnd=$stage>0?gmdate('Y-m-d H:i:s',time()+$stageSecs):null;
    $pdo->prepare("UPDATE games SET current_stage=?,stage_started_at=?,stage_ends_at=?,response_required=0,response_value=NULL WHERE id=?")->execute([$stage,$stageStart,$stageEnd,$id]);
    if($stage>0) set_state($id,'answer_revealed_stage_'.$stage,'0');
    log_game($id,'stage_forced',['stage'=>$stage]);
}elseif($action==='add_time'){
    $pdo->prepare("UPDATE games SET ends_at=datetime(COALESCE(ends_at,CURRENT_TIMESTAMP), '+5 minutes'),stage_ends_at=datetime(COALESCE(stage_ends_at,CURRENT_TIMESTAMP), '+5 minutes') WHERE id=?")->execute([$id]);log_game($id,'time_added',['minutes'=>5]);
}elseif($action==='gm_event'){
    $event=preg_replace('/[^a-z_]/','',strtolower((string)($_POST['event']??'boarding')));
    $allowed=['boarding','story_start','lights','engine','anomaly','coordinates','prepare_jump','jump','reset_scene'];
    if(!in_array($event,$allowed,true)) $event='boarding';
    set_state($id,'gm_event',$event);
    log_game($id,'gm_event',['event'=>$event,'stage'=>$g['current_stage']]);
}elseif($action==='reveal_answer'){
    $current=(int)$g['current_stage'];
    if($current>0){ set_state($id,'answer_revealed_stage_'.$current,'1'); log_game($id,'answer_revealed',['stage'=>$current]); }
}elseif($action==='hide_answer'){
    $current=(int)$g['current_stage'];
    if($current>0){ set_state($id,'answer_revealed_stage_'.$current,'0'); log_game($id,'answer_hidden',['stage'=>$current]); }
}elseif($action==='delete_player'){
    $playerId=(int)($_POST['player_id']??0);
    if($playerId>0){ $pdo->prepare('DELETE FROM players WHERE id=? AND game_id=?')->execute([$playerId,$id]); log_game($id,'player_deleted',['player_id'=>$playerId]); }
}elseif($action==='assign_role'){
    $playerId=(int)($_POST['player_id']??0);
    $roleKey=trim((string)($_POST['role_key']??''));
    $roleDef=player_role_definition($roleKey);
    if($playerId<=0 || !$roleDef){ flash('Rôle invalide.'); }
    else {
        $chk=$pdo->prepare('SELECT id FROM players WHERE game_id=? AND role_key=? AND id<>? LIMIT 1');
        $chk->execute([$id,$roleKey,$playerId]);
        if($chk->fetchColumn()) flash('Ce rôle est déjà attribué à un autre joueur.');
        else {
            $pdo->prepare('UPDATE players SET role_key=?,seat=?,ready=0 WHERE id=? AND game_id=?')->execute([$roleDef['role_key'],$roleDef['seat'],$playerId,$id]);
            log_game($id,'role_assigned',['player_id'=>$playerId,'role_key'=>$roleKey]);
            flash('Rôle attribué : '.$roleDef['label'].'. Le joueur devra confirmer sa position.');
        }
    }
}elseif($action==='delete_game'){
    log_game($id,'game_deleted');
    $pdo->prepare('DELETE FROM games WHERE id=?')->execute([$id]);
    redirect('/admin/index.php');
}elseif($action==='send_message'){
    $target=(int)($_POST['target_player_id']??0); $type=trim((string)($_POST['message_type']??'text')); $title=trim((string)($_POST['message_title']??'')); $body=trim((string)($_POST['message_body']??'')); $url='';
    if($target>0){$ck=$pdo->prepare('SELECT id FROM players WHERE id=? AND game_id=?');$ck->execute([$target,$id]);if(!$ck->fetchColumn())$target=0;}
    if(!in_array($type,['text','image','video','file'],true))$type='text';
    if(!empty($_FILES['message_file']['tmp_name']) && is_uploaded_file($_FILES['message_file']['tmp_name'])){
        $dir=__DIR__.'/../uploads/escape'; if(!is_dir($dir))mkdir($dir,0755,true); $name=preg_replace('/[^A-Za-z0-9._-]/','_',basename($_FILES['message_file']['name'])); $name=time().'_'.bin2hex(random_bytes(4)).'_'.$name; $dest=$dir.'/'.$name;
        if(move_uploaded_file($_FILES['message_file']['tmp_name'],$dest)){ $url='/uploads/escape/'.$name; $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION)); if(in_array($ext,['jpg','jpeg','png','gif','webp']))$type='image'; elseif(in_array($ext,['mp4','webm','mov']))$type='video'; else $type='file'; }
    }
    if($body==='' && $url===''){flash('Message vide.');} else { $pdo->prepare('INSERT INTO mailbox(game_id,sender_type,target_player_id,type,title,body,media_url,stage) VALUES(?,?,?,?,?,?,?,?)')->execute([$id,'admin',$target,$type,$title,$body,$url,(int)$g['current_stage']]); log_game($id,'mailbox_admin',['target_player_id'=>$target,'type'=>$type,'title'=>$title]); flash($target?'Message envoyé au joueur.':'Message envoyé à tout le monde.'); }
}elseif($action==='send_room_media'){
    $type=trim((string)($_POST['room_type']??'text')); $title=trim((string)($_POST['room_title']??'')); $body=trim((string)($_POST['room_body']??'')); $url=trim((string)($_POST['room_url']??''));
    if(!in_array($type,['text','image','video'],true))$type='text';
    if(!empty($_FILES['room_file']['tmp_name']) && is_uploaded_file($_FILES['room_file']['tmp_name'])){ $dir=__DIR__.'/../uploads/escape';if(!is_dir($dir))mkdir($dir,0755,true);$name=time().'_room_'.bin2hex(random_bytes(4)).'_'.preg_replace('/[^A-Za-z0-9._-]/','_',basename($_FILES['room_file']['name']));$dest=$dir.'/'.$name;if(move_uploaded_file($_FILES['room_file']['tmp_name'],$dest)){$url='/uploads/escape/'.$name;$ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));if(in_array($ext,['jpg','jpeg','png','gif','webp']))$type='image';elseif(in_array($ext,['mp4','webm','mov']))$type='video';}}
    $pdo->prepare('UPDATE room_media SET active=0 WHERE game_id=?')->execute([$id]);$pdo->prepare('INSERT INTO room_media(game_id,type,title,body,media_url,active) VALUES(?,?,?,?,?,1)')->execute([$id,$type,$title,$body,$url]);log_game($id,'room_media',['type'=>$type,'title'=>$title]);flash('Écran salle mis à jour.');
}elseif($action==='clear_room_media'){
    $pdo->prepare('UPDATE room_media SET active=0 WHERE game_id=?')->execute([$id]);log_game($id,'room_media_clear');flash('Écran salle effacé.');
}elseif($action==='hint'){
    $stage=(int)$g['current_stage']; if($stage<1)$stage=1; $content=game_content($g['slug']); $hint=$content[$stage]['answer_hint']??'Cherchez encore dans les éléments interactifs.'; set_state($id,'hint_stage_'.$stage,$hint); log_game($id,'hint_given',['stage'=>$stage]);
}elseif($action==='force_response'){
    $pdo->prepare("UPDATE games SET response_required=1 WHERE id=?")->execute([$id]);log_game($id,'response_forced',['stage'=>$g['current_stage']]);
}elseif($action==='next'){
    $next=min(5,(int)$g['current_stage']+1); $stageStart=$next>0?$now:null; $stageEnd=$next>0?gmdate('Y-m-d H:i:s',time()+$stageSecs):null;
    if($next>5){$pdo->prepare("UPDATE games SET status='finished',completed_at=? WHERE id=?")->execute([$now,$id]);}
    else {$pdo->prepare("UPDATE games SET current_stage=?,stage_started_at=?,stage_ends_at=?,response_required=0,response_value=NULL,status='running' WHERE id=?")->execute([$next,$stageStart,$stageEnd,$id]); set_state($id,'answer_revealed_stage_'.$next,'0');}
    log_game($id,'stage_advanced',['stage'=>$next]);
}elseif($action==='finish'){
    $pdo->prepare("UPDATE games SET status='finished',completed_at=? WHERE id=?")->execute([$now,$id]);log_game($id,'game_finished');
}
redirect('/admin/game.php?id='.$id);
