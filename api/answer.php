<?php
require_once __DIR__.'/../config/config.php'; start_app_session(); header('Content-Type: application/json; charset=utf-8');
if(empty($_SESSION['player_game_id'])){http_response_code(401);echo json_encode(['ok'=>false]);exit;}
$gameId=(int)$_SESSION['player_game_id'];$stage=(int)($_POST['stage']??0);$answer=trim((string)($_POST['answer']??''));
$st=db()->prepare("SELECT g.*,s.slug FROM games g JOIN stories s ON s.id=g.story_id WHERE g.id=?");$st->execute([$gameId]);$g=$st->fetch(PDO::FETCH_ASSOC);if(!$g){http_response_code(404);echo json_encode(['ok'=>false]);exit;}
$content=game_content($g['slug']);$mission=$content[$stage]??null;if(!$mission){http_response_code(400);echo json_encode(['ok'=>false]);exit;}
$correct=strtoupper(preg_replace('/[^A-Z0-9]/i','',$answer))===strtoupper(preg_replace('/[^A-Z0-9]/i','',$mission['answer']));
log_game($gameId,'answer',['stage'=>$stage,'correct'=>$correct]);
if($correct){$next=$stage+1;$stageSecs=stage_duration_seconds($g);if($next>5){db()->prepare("UPDATE games SET status='finished',response_required=0,response_value=?,completed_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$answer,$gameId]);echo json_encode(['ok'=>true,'correct'=>true,'finished'=>true,'message'=>'TIMELINE RESTORED']);exit;}$start=iso_now();$end=gmdate('Y-m-d H:i:s',time()+$stageSecs);db()->prepare("UPDATE games SET current_stage=?,stage_started_at=?,stage_ends_at=?,response_required=0,response_value=? WHERE id=?")->execute([$next,$start,$end,$answer,$gameId]);set_state($gameId,'answer_revealed_stage_'.$next,'0');echo json_encode(['ok'=>true,'correct'=>true,'finished'=>false,'next_stage'=>$next,'message'=>'MISSION VALIDÉE']);exit;}
echo json_encode(['ok'=>true,'correct'=>false,'finished'=>false,'message'=>$g['response_required']?'Réponse incorrecte. La mission reste verrouillée : demandez un indice ou utilisez une autre tentative.':'Réponse incorrecte. Continuez vos recherches.']);
