<?php
require_once __DIR__.'/../config/config.php'; require_admin();
header('Content-Type: application/json; charset=utf-8');
$pdo=db(); $id=(int)($_POST['id']??$_GET['id']??0); if(!$id){http_response_code(400);echo json_encode(['ok'=>false,'message'=>'Partie introuvable']);exit;}
$q=$pdo->prepare('SELECT * FROM games WHERE id=?');$q->execute([$id]);$g=$q->fetch(PDO::FETCH_ASSOC);if(!$g){http_response_code(404);echo json_encode(['ok'=>false]);exit;}
$action=(string)($_POST['action']??'state');
function gm_state(int $id): array {
 $pdo=db(); $q=$pdo->prepare('SELECT id,name,ready,role_key,last_seen_at FROM players WHERE game_id=? ORDER BY id');$q->execute([$id]);$players=$q->fetchAll(PDO::FETCH_ASSOC);$now=time();
 foreach($players as &$p){$p['online']=!empty($p['last_seen_at']) && ($now-strtotime($p['last_seen_at'])<=10);$p['role_assigned']=!empty($p['role_key']);unset($p['last_seen_at']);}unset($p);
 $m=$pdo->prepare('SELECT id,sender_type,sender_player_id,target_player_id,type,title,body,media_url,created_at,read_at FROM mailbox WHERE game_id=? AND active=1 ORDER BY id DESC LIMIT 30');$m->execute([$id]);
 $r=$pdo->prepare('SELECT id,type,title,body,media_url FROM room_media WHERE game_id=? AND active=1 ORDER BY id DESC LIMIT 1');$r->execute([$id]);
 return ['players'=>$players,'messages'=>$m->fetchAll(PDO::FETCH_ASSOC),'room_media'=>$r->fetch(PDO::FETCH_ASSOC)?:null];
}
if($action==='state'){echo json_encode(['ok'=>true,'state'=>gm_state($id)],JSON_UNESCAPED_UNICODE);exit;}
if($action==='assign_role'){
 $pid=(int)$_POST['player_id'];$rk=trim((string)$_POST['role_key']);$rd=player_role_definition($rk);
 if(!$pid||!$rd){echo json_encode(['ok'=>false,'message'=>'Rôle invalide']);exit;}
 $c=$pdo->prepare('SELECT id FROM players WHERE game_id=? AND role_key=? AND id<>?');$c->execute([$id,$rk,$pid]);if($c->fetchColumn()){echo json_encode(['ok'=>false,'message'=>'Ce rôle est déjà attribué']);exit;}
 $pdo->prepare('UPDATE players SET role_key=?,seat=?,ready=0 WHERE id=? AND game_id=?')->execute([$rd['role_key'],$rd['seat'],$pid,$id]);log_game($id,'role_assigned',['player_id'=>$pid,'role_key'=>$rk]);echo json_encode(['ok'=>true,'message'=>'Rôle attribué','state'=>gm_state($id)],JSON_UNESCAPED_UNICODE);exit;
}
if($action==='delete_player'){$pid=(int)$_POST['player_id'];$pdo->prepare('DELETE FROM players WHERE id=? AND game_id=?')->execute([$pid,$id]);log_game($id,'player_deleted',['player_id'=>$pid]);echo json_encode(['ok'=>true,'state'=>gm_state($id)],JSON_UNESCAPED_UNICODE);exit;}
if($action==='clear_mailbox'){$pdo->prepare('UPDATE mailbox SET active=0 WHERE game_id=?')->execute([$id]);log_game($id,'mailbox_cleared');echo json_encode(['ok'=>true,'state'=>gm_state($id)],JSON_UNESCAPED_UNICODE);exit;}
if($action==='send_message'){
 $target=(int)($_POST['target_player_id']??0);$type=(string)($_POST['message_type']??'text');$title=trim((string)($_POST['message_title']??''));$body=trim((string)($_POST['message_body']??''));$url='';
 if($target){$c=$pdo->prepare('SELECT id FROM players WHERE id=? AND game_id=?');$c->execute([$target,$id]);if(!$c->fetchColumn())$target=0;}
 if(!in_array($type,['text','image','video','file'],true))$type='text';
 if(!empty($_FILES['message_file']['tmp_name'])&&is_uploaded_file($_FILES['message_file']['tmp_name'])){$dir=__DIR__.'/../uploads/escape';if(!is_dir($dir))mkdir($dir,0755,true);$name=time().'_'.bin2hex(random_bytes(4)).'_'.preg_replace('/[^A-Za-z0-9._-]/','_',basename($_FILES['message_file']['name']));$dest=$dir.'/'.$name;if(move_uploaded_file($_FILES['message_file']['tmp_name'],$dest)){$url='/uploads/escape/'.$name;$ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));$type=in_array($ext,['jpg','jpeg','png','gif','webp'])?'image':(in_array($ext,['mp4','webm','mov'])?'video':'file');}}
 if($body===''&&$url===''){echo json_encode(['ok'=>false,'message'=>'Transmission vide']);exit;}
 $pdo->prepare('INSERT INTO mailbox(game_id,sender_type,target_player_id,type,title,body,media_url,stage,active) VALUES(?,?,?,?,?,?,?,?,1)')->execute([$id,'admin',$target,$type,$title,$body,$url,0]);log_game($id,'mailbox_admin',['target_player_id'=>$target,'type'=>$type]);echo json_encode(['ok'=>true,'message'=>'Transmission envoyée','state'=>gm_state($id)],JSON_UNESCAPED_UNICODE);exit;
}
if($action==='send_room'){
 $type=(string)($_POST['room_type']??'text');$title=trim((string)($_POST['room_title']??''));$body=trim((string)($_POST['room_body']??''));$url=trim((string)($_POST['room_url']??''));
 if(!in_array($type,['text','image','video'],true))$type='text';
 if(!empty($_FILES['room_file']['tmp_name'])&&is_uploaded_file($_FILES['room_file']['tmp_name'])){$dir=__DIR__.'/../uploads/escape';if(!is_dir($dir))mkdir($dir,0755,true);$name=time().'_room_'.bin2hex(random_bytes(4)).'_'.preg_replace('/[^A-Za-z0-9._-]/','_',basename($_FILES['room_file']['name']));$dest=$dir.'/'.$name;if(move_uploaded_file($_FILES['room_file']['tmp_name'],$dest)){$url='/uploads/escape/'.$name;$ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));$type=in_array($ext,['jpg','jpeg','png','gif','webp'])?'image':(in_array($ext,['mp4','webm','mov'])?'video':'text');}}
 $pdo->prepare('UPDATE room_media SET active=0 WHERE game_id=?')->execute([$id]);$pdo->prepare('INSERT INTO room_media(game_id,type,title,body,media_url,active) VALUES(?,?,?,?,?,1)')->execute([$id,$type,$title,$body,$url]);log_game($id,'room_media',['type'=>$type]);echo json_encode(['ok'=>true,'message'=>'Écran salle diffusé','state'=>gm_state($id)],JSON_UNESCAPED_UNICODE);exit;
}
if($action==='clear_room'){$pdo->prepare('UPDATE room_media SET active=0 WHERE game_id=?')->execute([$id]);log_game($id,'room_media_clear');echo json_encode(['ok'=>true,'message'=>'Retour au wallpaper','state'=>gm_state($id)],JSON_UNESCAPED_UNICODE);exit;}
if($action==='start'){$pdo->prepare("UPDATE games SET status='running',started_at=COALESCE(started_at,?),current_stage=0 WHERE id=?")->execute([iso_now(),$id]);log_game($id,'live_started');echo json_encode(['ok'=>true,'state'=>gm_state($id)],JSON_UNESCAPED_UNICODE);exit;}
if($action==='stop'){$pdo->prepare("UPDATE games SET status='paused' WHERE id=?")->execute([$id]);log_game($id,'live_paused');echo json_encode(['ok'=>true,'state'=>gm_state($id)],JSON_UNESCAPED_UNICODE);exit;}
if($action==='reset_live'){$pdo->prepare('UPDATE mailbox SET active=0 WHERE game_id=?')->execute([$id]);$pdo->prepare('UPDATE room_media SET active=0 WHERE game_id=?')->execute([$id]);log_game($id,'live_reset');echo json_encode(['ok'=>true,'message'=>'Régie réinitialisée','state'=>gm_state($id)],JSON_UNESCAPED_UNICODE);exit;}
http_response_code(400);echo json_encode(['ok'=>false,'message'=>'Action inconnue']);
