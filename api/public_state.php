<?php
require_once __DIR__.'/../config/config.php'; header('Content-Type: application/json; charset=utf-8');
$code=strtoupper(trim($_GET['code']??'')); if(!$code){http_response_code(400);echo json_encode(['ok'=>false]);exit;}
$st=db()->prepare("SELECT g.*,s.slug,s.title,s.subtitle FROM games g JOIN stories s ON s.id=g.story_id WHERE g.code=?");$st->execute([$code]);$g=$st->fetch(PDO::FETCH_ASSOC);if(!$g){http_response_code(404);echo json_encode(['ok'=>false]);exit;}
$stage=(int)$g['current_stage'];$content=game_content($g['slug']);$mission=$content[$stage]??null;$now=time();$stageRemaining=($g['status']==='running'&&$g['stage_ends_at'])?max(0,strtotime($g['stage_ends_at'])-$now):0;
$namesStmt=db()->prepare("SELECT name,seat,ready FROM players WHERE game_id=? ORDER BY id");$namesStmt->execute([$g['id']]);$crew=$namesStmt->fetchAll(PDO::FETCH_ASSOC);$names=array_column($crew,'name');$allReady=count($crew)>0 && count(array_filter($crew,fn($x)=>(int)$x['ready']===1))===count($crew);
$puzzleRaw=$stage?get_state((int)$g['id'],'puzzle_stage_'.$stage,'{}'):'{}';
$gmEvent=get_state((int)$g['id'],'gm_event','boarding');$puzzle=json_decode($puzzleRaw,true);if(!is_array($puzzle))$puzzle=[];$solved=$stage?get_state((int)$g['id'],'puzzle_stage_'.$stage.'_solved','0')==='1':false;
echo json_encode(['ok'=>true,'game'=>['code'=>$g['code'],'status'=>$g['status'],'mode'=>$g['mode'],'stage'=>$stage,'stage_remaining'=>$stageRemaining,'mission'=>$mission,'players'=>$names,'crew'=>$crew,'all_ready'=>$allReady,'puzzle'=>$puzzle,'puzzle_solved'=>$solved,'gm_event'=>$gmEvent]],JSON_UNESCAPED_UNICODE);
