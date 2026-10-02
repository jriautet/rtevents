<?php
require_once __DIR__.'/../config/config.php'; require_admin();
$story=(int)($_POST['story_id']??0); $duration=max(10,min(120,(int)($_POST['duration']??30))); $difficulty=$_POST['difficulty']??'normal'; $mode=$_POST['mode']??'local';
do{$code=random_code(6);$q=db()->prepare("SELECT id FROM games WHERE code=?");$q->execute([$code]);}while($q->fetch());
db()->prepare("INSERT INTO games(code,story_id,duration,difficulty,mode) VALUES(?,?,?,?,?)")->execute([$code,$story,$duration,$difficulty,$mode]);
$id=(int)db()->lastInsertId(); redirect('/admin/game.php?id='.$id);
