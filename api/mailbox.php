<?php
require_once __DIR__.'/../config/config.php'; start_app_session(); header('Content-Type: application/json; charset=utf-8');
if(empty($_SESSION['player_game_id']) || empty($_SESSION['player_id'])){http_response_code(401);echo json_encode(['ok'=>false]);exit;}
$gameId=(int)$_SESSION['player_game_id']; $playerId=current_player_id($gameId); if(!$playerId){http_response_code(410);echo json_encode(['ok'=>false,'removed'=>true]);exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){
 $action=trim((string)($_POST['action']??'')); $body=trim((string)($_POST['body']??''));
 if(!in_array($action,['answer','hint_request','ack'],true)){http_response_code(400);echo json_encode(['ok'=>false]);exit;}
 if($action==='ack'){ $mid=(int)($_POST['message_id']??0); if($mid) db()->prepare('UPDATE mailbox SET read_at=CURRENT_TIMESTAMP WHERE id=? AND game_id=? AND target_player_id=?')->execute([$mid,$gameId,$playerId]); echo json_encode(['ok'=>true]);exit; }
 $title=$action==='hint_request'?'DEMANDE D’INDICE':'RÉPONSE DU JOUEUR';
 if($body==='') $body=$action==='hint_request'?'Le joueur demande un indice.':'Le joueur souhaite transmettre une réponse.';
 db()->prepare('INSERT INTO mailbox(game_id,sender_type,sender_player_id,target_player_id,type,title,body) VALUES(?,?,?,?,?,?,?)')->execute([$gameId,'player',$playerId,null,'text',$title,$body]);
 log_game($gameId,'mailbox_player',['player_id'=>$playerId,'action'=>$action,'body'=>$body]); echo json_encode(['ok'=>true]); exit;
}
$q=db()->prepare("SELECT id,sender_type,sender_player_id,target_player_id,type,title,body,media_url,created_at,read_at FROM mailbox WHERE game_id=? AND (target_player_id IS NULL OR target_player_id=?) ORDER BY id DESC LIMIT 30");$q->execute([$gameId,$playerId]);
$rows=$q->fetchAll(PDO::FETCH_ASSOC); foreach($rows as &$r){if($r['target_player_id']===null && $r['sender_type']==='admin'){} } unset($r);
$unread=count(array_filter($rows,fn($r)=>$r['sender_type']==='admin' && empty($r['read_at'])));
echo json_encode(['ok'=>true,'messages'=>$rows,'unread'=>$unread],JSON_UNESCAPED_UNICODE);
