<?php
require_once __DIR__.'/../config/config.php'; start_app_session();
if($_SERVER['REQUEST_METHOD']!=='POST') redirect('/admin/login.php');
$s=db()->prepare("SELECT * FROM admins WHERE username=?"); $s->execute([trim($_POST['username']??'')]); $a=$s->fetch(PDO::FETCH_ASSOC);
if(!$a || !password_verify($_POST['password']??'',$a['password_hash'])){flash('Identifiants incorrects.');redirect('/admin/login.php');}
$_SESSION['admin_id']=$a['id']; $_SESSION['admin_user']=$a['username']; redirect('/admin/index.php');
