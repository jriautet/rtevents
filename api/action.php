<?php
require_once __DIR__.'/../config/config.php'; start_app_session(); header('Content-Type: application/json; charset=utf-8');
if(empty($_SESSION['player_game_id'])){http_response_code(401);echo json_encode(['ok'=>false]);exit;}
$gameId=(int)$_SESSION['player_game_id']; $playerId=(int)($_SESSION['player_id']??0);
$st=db()->prepare("SELECT g.*,s.slug FROM games g JOIN stories s ON s.id=g.story_id WHERE g.id=?");$st->execute([$gameId]);$g=$st->fetch(PDO::FETCH_ASSOC);
if(!$g){http_response_code(404);echo json_encode(['ok'=>false]);exit;}
$stage=(int)$g['current_stage']; $action=trim((string)($_POST['action']??'')); $value=trim((string)($_POST['value']??'')); $rq=db()->prepare("SELECT role_key FROM players WHERE id=? AND game_id=?");$rq->execute([$playerId,$gameId]);$myRole=(string)$rq->fetchColumn();
$content=game_content($g['slug']); if($action==='ready'){
    if(!$playerId){echo json_encode(['ok'=>false,'message'=>'Joueur non identifié.']);exit;}
    db()->prepare("UPDATE players SET ready=1,last_seen_at=CURRENT_TIMESTAMP WHERE id=? AND game_id=?")->execute([$playerId,$gameId]);
    $ready=all_players_ready($gameId);
    if($ready){
        $q=$pdo=db(); $gs=$q->prepare("SELECT * FROM games WHERE id=?");$gs->execute([$gameId]);$gg=$gs->fetch(PDO::FETCH_ASSOC);$now=iso_now();$secs=stage_duration_seconds($gg);
        if($gg['status']==='waiting'){
            $q->prepare("UPDATE games SET status='running',current_stage=1,started_at=?,ends_at=datetime(?,'+'||duration||' minutes'),stage_started_at=?,stage_ends_at=datetime(?,'+'||?||' seconds'),response_required=0,response_value=NULL WHERE id=?")->execute([$now,$now,$now,$now,$secs,$gameId]);
            log_game($gameId,'crew_ready_start');
        }
    }
    echo json_encode(['ok'=>true,'ready'=>true,'all_ready'=>$ready,'message'=>$ready?'Équipage complet. La machine démarre.':'Position confirmée. Attendez les autres membres de l’équipage.'],JSON_UNESCAPED_UNICODE);exit;
}
if(!$stage||!isset($content[$stage])){echo json_encode(['ok'=>false,'message'=>'La machine est en attente.']);exit;}
function puzzle_state(int $gameId,int $stage): array { $raw=get_state($gameId,'puzzle_stage_'.$stage,'{}'); $v=json_decode($raw,true); return is_array($v)?$v:[]; }
function save_puzzle_state(int $gameId,int $stage,array $state): void { set_state($gameId,'puzzle_stage_'.$stage,json_encode($state,JSON_UNESCAPED_UNICODE)); }
function finish_puzzle_if_ready(int $gameId,int $stage,array $state): bool { if(empty($state['_solved'])) return false; set_state($gameId,'puzzle_stage_'.$stage.'_solved','1'); log_game($gameId,'puzzle_solved',['stage'=>$stage,'player_id'=>(int)($_SESSION['player_id']??0)]); return true; }
$s=puzzle_state($gameId,$stage); $changed=false; $message=''; $solved=false;
if($stage===1){
  // Épreuve 1 = mise en scène physique / narration du maître du jeu.
  // Les joueurs n'ont pas à remplir un formulaire : le logiciel synchronise uniquement la salle.
  if($action==='ack'){
      $message='Bien reçu. Restez à votre poste et écoutez le maître du jeu.'; $changed=true;
      $s['ack_'.$playerId]=1;
  }
  $message=$message ?: 'La DeLorean attend. Le maître du jeu dirige la scène.';
} elseif($stage===2){
  if($action==='archive'){
    $id=$value;$s['opened'][$id]=1;$changed=true;$message='Dossier consulté.';
    if(count($s['opened']??[])>=3){$s['archive_opened']=1;$message='Les trois dossiers ont été consultés.';}
  } elseif($action==='timeline'){
    $order=preg_replace('/[^ABC]/','',strtoupper($value));$s['timeline']=$order;$changed=true;if($order==='BCA'){$s['timeline_ok']=1;$message='Chronologie restaurée : 1955 → 1985 → anomalie.';}else{$message='Cette chronologie provoque une contradiction temporelle.';}
  } elseif($action==='photo'){
    $angle=((int)$value)%360;if($angle<0)$angle+=360;$s['photo_angle']=$angle;$changed=true;if($angle===180){$s['photo_ok']=1;$message='La photographie révèle le détail caché.';}else{$message='L’image ne correspond pas encore au négatif.';}
  }
  if(!empty($s['archive_opened'])&&!empty($s['timeline_ok'])&&!empty($s['photo_ok'])){$s['_solved']=1;$solved=true;}
} elseif($stage===3){
  if($action==='frequency'){$freq=round(max(87,min(90,(float)$value)),1);$s['freq']=$freq;$changed=true;if(abs($freq-88.7)<0.01){$s['freq_ok']=1;$message='La radio accroche une transmission venue de 1985.';}else{$message='Parasites… aucune transmission stable.';}}
  elseif($action==='morse'){$seq=preg_replace('/[^1234]/','',$value);$s['morse']=$seq;$changed=true;if($seq==='3142'){$s['morse_ok']=1;$message='Le signal répond : RETOUR.';}else{$message='Signal incorrect. Les impulsions se désynchronisent.';}}
  elseif($action==='tape'){$s['tape_ok']=1;$changed=true;$message='La bande magnétique révèle la séquence des impulsions.';}
  if(!empty($s['freq_ok'])&&!empty($s['morse_ok'])&&!empty($s['tape_ok'])){$s['_solved']=1;$solved=true;}
} elseif($stage===4){
  if($action==='circuit'){$field=$value;$allowed=['year'=>'1985','month'=>'10','day'=>'26','hour'=>'21','minute'=>'00'];$val=trim((string)($_POST['setting']??''));if(isset($allowed[$field])){$s[$field]=$val;$changed=true;$message=strtoupper($field).' RÉGLÉ : '.$val;if($val===$allowed[$field]){$s[$field.'_ok']=1;$message.= ' · VERROUILLÉ';}else{$s[$field.'_ok']=0;}}}
  elseif($action==='ignition'){$seq=$s['ignition']??[];$seq[]=$value;$s['ignition']=array_slice($seq,-3);$changed=true;$message='Commande DeLorean enregistrée.';if(($s['ignition']??[])===['power','brake','launch']){$s['ignition_ok']=1;$message='SÉQUENCE DE DÉPART ARMÉE.';}}
  if(!empty($s['year_ok'])&&!empty($s['month_ok'])&&!empty($s['day_ok'])&&!empty($s['hour_ok'])&&!empty($s['minute_ok'])&&!empty($s['ignition_ok'])){$s['_solved']=1;$solved=true;}
} elseif($stage===5){
  if($action==='slider'){$field=$value;$num=(int)($_POST['setting']??0);if($field==='power')$s['power']=$num;if($field==='speed')$s['speed']=$num;$changed=true;$message='Réglage du flux modifié.';}
  elseif($action==='switch'){$seq=$s['switches']??[];$seq[]=$value;$s['switches']=array_slice($seq,-3);$changed=true;$message='Interrupteur enclenché.';if(($s['switches']??[])===['flux','capacitor','speed']){$s['switch_ok']=1;$message='SYNCHRONISATION DES TROIS CIRCUITS.';}}
  if((int)($s['power']??0)===121&&(int)($s['speed']??0)===88&&!empty($s['switch_ok'])){$s['_solved']=1;$solved=true;}
}
if($changed)save_puzzle_state($gameId,$stage,$s);
if($solved){finish_puzzle_if_ready($gameId,$stage,$s);}
log_game($gameId,'puzzle_action',['stage'=>$stage,'action'=>$action,'value'=>$value,'player_id'=>$playerId,'solved'=>$solved]);
echo json_encode(['ok'=>true,'message'=>$message,'solved'=>$solved,'state'=>$s],JSON_UNESCAPED_UNICODE);
