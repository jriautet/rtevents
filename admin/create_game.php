<?php
require_once __DIR__.'/../config/config.php'; require_admin();
$story=(int)($_POST['story_id']??0);
$duration=max(20,min(120,(int)($_POST['duration']??30)));
$difficulty=$_POST['difficulty']??'normal';
$mode=$_POST['mode']??'local';
$variant=strtoupper(substr(trim($_POST['variant']??'A'),0,1));
if(!in_array($variant,['A','B','C'],true)) $variant='A';
do{$code=random_code(6);$q=db()->prepare("SELECT id FROM games WHERE code=?");$q->execute([$code]);}while($q->fetch());
db()->prepare("INSERT INTO games(code,story_id,duration,difficulty,mode,variant) VALUES(?,?,?,?,?,?)")->execute([$code,$story,$duration,$difficulty,$mode,$variant]);
$id=(int)db()->lastInsertId();
log_game($id,'game_created',['mode'=>$mode,'duration'=>$duration,'difficulty'=>$difficulty,'variant'=>$variant]);
redirect('/admin/game.php?id='.$id);
