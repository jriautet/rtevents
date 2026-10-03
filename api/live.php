<?php
require_once __DIR__.'/../config/config.php';

// RT ESCAPE live event stream. The browser keeps one connection open instead of polling.
@set_time_limit(0);
@ini_set('output_buffering','off');
@ini_set('zlib.output_compression','0');
while (ob_get_level() > 0) @ob_end_flush();
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('X-Accel-Buffering: no');

$channel = trim((string)($_GET['channel'] ?? 'player'));
if (!in_array($channel, ['player','room'], true)) $channel='player';

$pdo=db();
$gameId=0;
$playerId=0;
$code='';
if ($channel==='player') {
    start_app_session();
    if (empty($_SESSION['player_game_id'])) { http_response_code(401); exit; }
    $gameId=(int)$_SESSION['player_game_id'];
    $playerId=current_player_id($gameId);
    if (!$playerId) { http_response_code(410); exit; }
} else {
    $code=strtoupper(trim((string)($_GET['code'] ?? '')));
    if ($code==='') { http_response_code(400); exit; }
    $q=$pdo->prepare('SELECT id FROM games WHERE code=? LIMIT 1'); $q->execute([$code]); $gameId=(int)$q->fetchColumn();
    if (!$gameId) { http_response_code(404); exit; }
}

echo ": rt-escape-live\n\n"; @flush();
$lastLog=0; $lastMailbox=0; $lastRoom=0; $lastCrewHash=''; $started=microtime(true); $lastHeartbeat=$started; $lastSeen=$started;

while (!connection_aborted() && (microtime(true)-$started) < 55) {
    $now=microtime(true);
    if ($channel==='player' && ($now-$lastSeen)>=5) {
        $pdo->prepare('UPDATE players SET last_seen_at=CURRENT_TIMESTAMP WHERE id=? AND game_id=?')->execute([$playerId,$gameId]);
        $lastSeen=$now;
    }

    $changed=false;
    $logQ=$pdo->prepare('SELECT COALESCE(MAX(id),0) FROM logs WHERE game_id=?'); $logQ->execute([$gameId]); $logId=(int)$logQ->fetchColumn();
    if ($logId !== $lastLog) { $lastLog=$logId; $changed=true; }

    if ($channel==='player') {
        $mQ=$pdo->prepare('SELECT COALESCE(MAX(id),0) FROM mailbox WHERE game_id=? AND (target_player_id IS NULL OR target_player_id=?) AND stage=?');
        // Stage is read below so a stage transition log still causes a refresh.
        $gq=$pdo->prepare('SELECT current_stage FROM games WHERE id=?'); $gq->execute([$gameId]); $currentStage=(int)$gq->fetchColumn();
        $mQ->execute([$gameId,$playerId,$currentStage]); $mailId=(int)$mQ->fetchColumn();
        if ($mailId !== $lastMailbox) { $lastMailbox=$mailId; $changed=true; }
    } else {
        $rQ=$pdo->prepare('SELECT COALESCE(MAX(id),0) FROM room_media WHERE game_id=? AND active=1'); $rQ->execute([$gameId]); $roomId=(int)$rQ->fetchColumn();
        if ($roomId !== $lastRoom) { $lastRoom=$roomId; $changed=true; }
        $crewQ=$pdo->prepare('SELECT id,name,ready,last_seen_at FROM players WHERE game_id=? ORDER BY id'); $crewQ->execute([$gameId]); $crew=$crewQ->fetchAll(PDO::FETCH_ASSOC);
        $hash=sha1(json_encode(array_map(fn($x)=>[$x['id'],$x['name'],$x['ready'],$x['last_seen_at']],$crew)));
        if ($hash !== $lastCrewHash) { $lastCrewHash=$hash; $changed=true; }
    }

    if ($changed) {
        echo "event: refresh\n";
        echo "data: {\"ts\":".time()."}\n\n";
        @flush();
    }
    if (($now-$lastHeartbeat)>=12) {
        echo ": heartbeat ".time()."\n\n"; @flush();
        $lastHeartbeat=$now;
    }
    usleep(300000);
}
