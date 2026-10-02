<?php
require_once __DIR__.'/../config/config.php'; require_admin();
$id=(int)($_POST['id']??0); $action=$_POST['action']??''; $pdo=db();
if($action==='start') $pdo->prepare("UPDATE games SET status='running', started_at=COALESCE(started_at,CURRENT_TIMESTAMP), ends_at=datetime(CURRENT_TIMESTAMP, '+'||duration||' minutes') WHERE id=?")->execute([$id]);
elseif($action==='pause') $pdo->prepare("UPDATE games SET status='paused' WHERE id=?")->execute([$id]);
elseif($action==='reset') $pdo->prepare("UPDATE games SET status='waiting',current_stage=0,started_at=NULL,ends_at=NULL WHERE id=?")->execute([$id]);
elseif($action==='stage') $pdo->prepare("UPDATE games SET current_stage=? WHERE id=?")->execute([(int)($_POST['stage']??0),$id]);
elseif($action==='add_time') $pdo->prepare("UPDATE games SET ends_at=datetime(COALESCE(ends_at,CURRENT_TIMESTAMP), '+5 minutes') WHERE id=?")->execute([$id]);
redirect('/admin/game.php?id='.$id);
