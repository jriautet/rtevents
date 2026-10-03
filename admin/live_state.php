<?php
require_once __DIR__.'/../config/config.php'; require_admin();
header('Content-Type: application/json; charset=utf-8');
$id=(int)($_GET['id']??0); if(!$id){http_response_code(400);echo json_encode(['ok'=>false]);exit;}
$q=db()->prepare('SELECT id,name,seat,role_key,ready,last_seen_at FROM players WHERE game_id=? ORDER BY id');$q->execute([$id]);$players=$q->fetchAll(PDO::FETCH_ASSOC);
$defs=player_role_definitions();$now=time();
foreach($players as &$p){$rd=$defs[$p['role_key']]??null;$p['role_label']=$rd['label']??null;$p['online']=($p['last_seen_at'] && ($now-strtotime($p['last_seen_at'])<=10));unset($p['last_seen_at']);}unset($p);
$gq=db()->prepare('SELECT current_stage,status,stage_ends_at FROM games WHERE id=?');$gq->execute([$id]);$g=$gq->fetch(PDO::FETCH_ASSOC);
echo json_encode(['ok'=>true,'players'=>$players,'stage'=>(int)$g['current_stage'],'status'=>$g['status'],'stage_remaining'=>($g['status']==='running'&&$g['stage_ends_at'])?max(0,strtotime($g['stage_ends_at'])-time()):0],JSON_UNESCAPED_UNICODE);
