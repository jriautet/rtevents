<?php
require_once __DIR__.'/../config/config.php';
start_app_session();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/game/login.php');
$code = strtoupper(trim($_POST['code'] ?? ''));
$name = trim($_POST['name'] ?? '');
$stmt = db()->prepare("SELECT g.*,s.slug,s.title FROM games g JOIN stories s ON s.id=g.story_id WHERE g.code=? LIMIT 1");
$stmt->execute([$code]); $game=$stmt->fetch(PDO::FETCH_ASSOC);
if (!$game || $game['status']==='finished') { flash('Code invalide ou partie terminée.'); redirect('/game/login.php'); }
if ($name==='') { flash('Indique ton prénom ou ton pseudo.'); redirect('/game/login.php'); }
db()->prepare("INSERT INTO players(game_id,name) VALUES(?,?)")->execute([$game['id'],mb_substr($name,0,30)]);
$_SESSION['player_game_id']=(int)$game['id']; $_SESSION['player_name']=mb_substr($name,0,30); $_SESSION['player_code']=$code;
redirect('/game/play.php');
