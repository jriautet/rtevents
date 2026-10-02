<?php
declare(strict_types=1);

const APP_NAME = 'RT EVENTS — ESCAPE';
const APP_URL = 'https://rtevents.eu';
const DB_PATH = __DIR__ . '/../storage/escape.sqlite';
const SESSION_NAME = 'rt_escape_session';
const DEFAULT_ADMIN_USER = 'admin';
const DEFAULT_ADMIN_PASSWORD = 'ChangeMe!2026';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
    return $pdo;
}
function start_app_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}
function e(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): never { header('Location: '.$url); exit; }
function flash(?string $message = null): ?string {
    start_app_session();
    if ($message !== null) { $_SESSION['flash'] = $message; return null; }
    $m = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $m;
}
function is_admin(): bool { start_app_session(); return !empty($_SESSION['admin_id']); }
function require_admin(): void { if (!is_admin()) redirect('/admin/login.php'); }
function random_code(int $length=6): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $out=''; for($i=0;$i<$length;$i++) $out .= $chars[random_int(0,strlen($chars)-1)];
    return $out;
}
