<?php
require_once __DIR__.'/../config/config.php'; start_app_session(); header('Content-Type: application/json; charset=utf-8');
if(empty($_SESSION['player_game_id'])){http_response_code(401);echo json_encode(['ok'=>false]);exit;}
$gameId=(int)$_SESSION['player_game_id'];
$st=db()->prepare("SELECT g.*,s.slug,s.title,s.subtitle FROM games g JOIN stories s ON s.id=g.story_id WHERE g.id=?");$st->execute([$gameId]);$g=$st->fetch(PDO::FETCH_ASSOC);
if(!$g){http_response_code(404);echo json_encode(['ok'=>false]);exit;}
$playerId=(int)($_SESSION['player_id']??0);
if($playerId){
    $ps=db()->prepare("SELECT id FROM players WHERE id=? AND game_id=?"); $ps->execute([$playerId,$gameId]);
    if(!$ps->fetchColumn()){ unset($_SESSION['player_id'],$_SESSION['player_game_id'],$_SESSION['player_token']); http_response_code(410); echo json_encode(['ok'=>false,'removed'=>true,'message'=>'Tu as été retiré de la partie.']); exit; }
    db()->prepare("UPDATE players SET last_seen_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$playerId]);
}
$stage=(int)$g['current_stage'];
$content=game_content($g['slug']); $mission=$content[$stage]??null;
$now=time();
$stageRemaining=0; $totalRemaining=0;
if($g['status']==='running'){
    $stageRemaining=max(0,strtotime((string)$g['stage_ends_at'])-$now);
    $totalRemaining=max(0,strtotime((string)$g['ends_at'])-$now);
}
if($g['status']==='running' && ($stageRemaining<=0 || $totalRemaining<=0) && !$g['response_required']){
    if($totalRemaining<=0){db()->prepare("UPDATE games SET status='finished',response_required=1,completed_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$gameId]);$g['status']='finished';$g['response_required']=1;}
    else {db()->prepare("UPDATE games SET response_required=1 WHERE id=?")->execute([$gameId]);$g['response_required']=1;}
}
$hint=get_state($gameId,'hint_stage_'.$stage,null);
$answerRevealed=$stage>0 ? answer_revealed($gameId,$stage) : false;
$revealedAnswer=($answerRevealed && $mission) ? $mission['answer'] : null;
$discoveries=db()->prepare("SELECT discovery_key FROM discoveries WHERE game_id=? AND (player_id IS NULL OR player_id=?) AND stage=? ORDER BY id");$discoveries->execute([$gameId,$playerId,$stage]);
$found=array_column($discoveries->fetchAll(PDO::FETCH_ASSOC),'discovery_key');
$players=db()->prepare("SELECT name FROM players WHERE game_id=? ORDER BY id");$players->execute([$gameId]);$names=array_column($players->fetchAll(PDO::FETCH_ASSOC),'name');
echo json_encode(['ok'=>true,'game'=>['id'=>(int)$g['id'],'code'=>$g['code'],'status'=>$g['status'],'mode'=>$g['mode'],'duration'=>(int)$g['duration'],'difficulty'=>$g['difficulty'],'variant'=>$g['variant'],'stage'=>$stage,'response_required'=>(bool)$g['response_required'],'response_value'=>$g['response_value'], 'answer_revealed'=>$answerRevealed, 'revealed_answer'=>$revealedAnswer,'stage_remaining'=>$stageRemaining,'total_remaining'=>$totalRemaining,'mission'=>$mission,'hint'=>$hint,'discoveries'=>$found,'players'=>$names]],JSON_UNESCAPED_UNICODE);
