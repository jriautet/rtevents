<?php
require_once __DIR__.'/../config/config.php'; header('Content-Type: application/json; charset=utf-8');
$code=strtoupper(trim($_GET['code']??'')); if(!$code){http_response_code(400);echo json_encode(['ok'=>false]);exit;}
$st=db()->prepare("SELECT g.*,s.slug,s.title,s.subtitle FROM games g JOIN stories s ON s.id=g.story_id WHERE g.code=?");$st->execute([$code]);$g=$st->fetch(PDO::FETCH_ASSOC);if(!$g){http_response_code(404);echo json_encode(['ok'=>false]);exit;}
$stage=(int)$g['current_stage'];$content=game_content($g['slug']);$mission=$content[$stage]??null;$now=time();$stageRemaining=($g['status']==='running'&&$g['stage_ends_at'])?max(0,strtotime($g['stage_ends_at'])-$now):0;
$namesStmt=db()->prepare("SELECT id,name,seat,ready,role_key,last_seen_at FROM players WHERE game_id=? ORDER BY id");$namesStmt->execute([$g['id']]);$crew=$namesStmt->fetchAll(PDO::FETCH_ASSOC);
$nowTs=time(); foreach($crew as &$member){ $member['online']=($member['last_seen_at'] && ($nowTs-strtotime($member['last_seen_at'])<=8)); $member['role_assigned']=!empty($member['role_key']); unset($member['role_key'],$member['last_seen_at']); } unset($member);$names=array_column($crew,'name');$allReady=count($crew)>0 && count(array_filter($crew,fn($x)=>(int)$x['ready']===1))===count($crew);
$puzzleRaw=$stage?get_state((int)$g['id'],'puzzle_stage_'.$stage,'{}'):'{}';
$gmEvent=get_state((int)$g['id'],'gm_event','boarding');$puzzle=json_decode($puzzleRaw,true);if(!is_array($puzzle))$puzzle=[];$solved=$stage?get_state((int)$g['id'],'puzzle_stage_'.$stage.'_solved','0')==='1':false;
$rm=db()->prepare("SELECT id,type,title,body,media_url FROM room_media WHERE game_id=? AND active=1 ORDER BY id DESC LIMIT 1");$rm->execute([$g['id']]);$roomMedia=$rm->fetch(PDO::FETCH_ASSOC)?:null;
echo json_encode(['ok'=>true,'game'=>['code'=>$g['code'],'status'=>$g['status'],'mode'=>$g['mode'],'stage'=>$stage,'stage_remaining'=>$stageRemaining,'mission'=>$mission,'players'=>$names,'crew'=>$crew,'all_ready'=>$allReady,'puzzle'=>$puzzle,'puzzle_solved'=>$solved,'gm_event'=>$gmEvent,'room_media'=>$roomMedia,'join_url'=>APP_URL.'/game/login.php?code='.rawurlencode($g['code'])]],JSON_UNESCAPED_UNICODE);
