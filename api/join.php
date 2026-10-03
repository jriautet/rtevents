<?php
require_once __DIR__.'/../config/config.php';
start_app_session();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/game/login.php');
$code = strtoupper(trim($_POST['code'] ?? ''));
$name = trim($_POST['name'] ?? '');
$stmt = db()->prepare("SELECT g.*,s.slug,s.title FROM games g JOIN stories s ON s.id=g.story_id WHERE g.code=? LIMIT 1");
$stmt->execute([$code]); $game=$stmt->fetch(PDO::FETCH_ASSOC);
if (!$game || $game['status']==='finished') { flash('Code invalide ou partie terminée.'); redirect('/game/login.php'); }
if ($name==='') { flash('Indique ton prénom ou ton pseudo.'); redirect('/game/login.php?code='.rawurlencode($code)); }
$name=mb_substr($name,0,30);
$token=bin2hex(random_bytes(16));
$countStmt=db()->prepare("SELECT COUNT(*) FROM players WHERE game_id=?"); $countStmt->execute([$game['id']]); $count=(int)$countStmt->fetchColumn();
if($count>=6){ flash('Cette DeLorean est complète.'); redirect('/game/login.php?code='.rawurlencode($code)); }
db()->prepare("INSERT INTO players(game_id,name,player_token,seat,role_key,ready) VALUES(?,?,?,?,?,0)")->execute([$game['id'],$name,$token,null,null]);
$playerId=(int)db()->lastInsertId();
$_SESSION['player_game_id']=(int)$game['id']; $_SESSION['player_id']=$playerId; $_SESSION['player_token']=$token; $_SESSION['player_name']=$name; $_SESSION['player_code']=$code;
redirect('/game/play.php');
