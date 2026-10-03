<?php
require_once __DIR__.'/../config/config.php'; start_app_session(); header('Content-Type: application/json; charset=utf-8');
if(empty($_SESSION['player_game_id'])){http_response_code(401);echo json_encode(['ok'=>false]);exit;}
$gameId=(int)$_SESSION['player_game_id'];$playerId=current_player_id($gameId);$key=trim($_POST['key']??'');$stage=(int)($_POST['stage']??0);
if(!$key||$stage<1){http_response_code(400);echo json_encode(['ok'=>false,'error'=>'Paramètres invalides']);exit;}
$st=db()->prepare("SELECT g.*,s.slug FROM games g JOIN stories s ON s.id=g.story_id WHERE g.id=?");$st->execute([$gameId]);$g=$st->fetch(PDO::FETCH_ASSOC);$content=game_content($g['slug']);$obj=null;foreach(($content[$stage]['objects']??[]) as $o)if($o['key']===$key)$obj=$o;
if(!$obj){http_response_code(404);echo json_encode(['ok'=>false]);exit;}
db()->prepare("INSERT OR IGNORE INTO discoveries(game_id,player_id,stage,discovery_key,content) VALUES(?,?,?,?,?)")->execute([$gameId,$g['mode']==='connected'?$playerId:null,$stage,$key,$obj['clue']]);
log_game($gameId,'discovery',['stage'=>$stage,'key'=>$key,'player_id'=>$playerId]);
echo json_encode(['ok'=>true,'key'=>$key,'title'=>$obj['title'],'text'=>$obj['text'],'clue'=>$obj['clue']],JSON_UNESCAPED_UNICODE);
