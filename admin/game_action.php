<?php
require_once __DIR__.'/../config/config.php'; require_admin();
$id=(int)($_POST['id']??0); $action=$_POST['action']??''; $pdo=db();
$s=$pdo->prepare("SELECT g.*,s.slug FROM games g JOIN stories s ON s.id=g.story_id WHERE g.id=?");$s->execute([$id]);$g=$s->fetch(PDO::FETCH_ASSOC);if(!$g) redirect('/admin/index.php');
$now=iso_now(); $stageSecs=stage_duration_seconds($g);
if($action==='start'){
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
}elseif($action==='reveal_answer'){
    $current=(int)$g['current_stage'];
    if($current>0){ set_state($id,'answer_revealed_stage_'.$current,'1'); log_game($id,'answer_revealed',['stage'=>$current]); }
}elseif($action==='hide_answer'){
    $current=(int)$g['current_stage'];
    if($current>0){ set_state($id,'answer_revealed_stage_'.$current,'0'); log_game($id,'answer_hidden',['stage'=>$current]); }
}elseif($action==='delete_player'){
    $playerId=(int)($_POST['player_id']??0);
    if($playerId>0){ $pdo->prepare('DELETE FROM players WHERE id=? AND game_id=?')->execute([$playerId,$id]); rebalance_player_roles($id); log_game($id,'player_deleted',['player_id'=>$playerId]); }
}elseif($action==='delete_game'){
    log_game($id,'game_deleted');
    $pdo->prepare('DELETE FROM games WHERE id=?')->execute([$id]);
    redirect('/admin/index.php');
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
