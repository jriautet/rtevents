<?php
require_once __DIR__.'/../config/config.php';
@set_time_limit(0);@ini_set('output_buffering','off');@ini_set('zlib.output_compression','0');while(ob_get_level()>0)@ob_end_flush();
header('Content-Type: text/event-stream; charset=utf-8');header('Cache-Control: no-cache, no-store, must-revalidate');header('X-Accel-Buffering: no');
$channel=(string)($_GET['channel']??'player');$pdo=db();$gameId=0;$playerId=0;
if($channel==='player'){start_app_session();$token=trim((string)($_GET['player_token']??''));$gameId=(int)($_SESSION['player_game_id']??0);if($token){$q=$pdo->prepare('SELECT game_id,id FROM players WHERE player_token=? LIMIT 1');$q->execute([$token]);$r=$q->fetch(PDO::FETCH_ASSOC);if($r){$gameId=(int)$r['game_id'];$playerId=(int)$r['id'];}}if(!$gameId){http_response_code(401);exit;}}
elseif($channel==='admin'){require_admin();$gameId=(int)($_GET['id']??0);}
else{$code=strtoupper(trim((string)($_GET['code']??'')));$q=$pdo->prepare('SELECT id FROM games WHERE code=?');$q->execute([$code]);$gameId=(int)$q->fetchColumn();}
if(!$gameId){http_response_code(404);exit;}
echo ": rt-escape-live\n\n";@flush();$last=0;$started=microtime(true);$lastBeat=$started;
while(!connection_aborted()&&(microtime(true)-$started)<18){$q=$pdo->prepare('SELECT COALESCE(MAX(id),0) FROM logs WHERE game_id=?');$q->execute([$gameId]);$id=(int)$q->fetchColumn();if($id!==$last){$last=$id;echo "event: refresh\n";echo 'data: {"id":'.$id."}\n\n";@flush();}if(microtime(true)-$lastBeat>=8){echo ': heartbeat '.time()."\n\n";@flush();$lastBeat=microtime(true);}if($channel==='player'&&$playerId){$pdo->prepare('UPDATE players SET last_seen_at=CURRENT_TIMESTAMP WHERE id=? AND game_id=?')->execute([$playerId,$gameId]);}usleep(700000);} 
