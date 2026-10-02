<?php
require_once __DIR__ . '/../config/config.php';
$pdo = db();
$pdo->exec("CREATE TABLE IF NOT EXISTS admins (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 username TEXT UNIQUE NOT NULL,
 password_hash TEXT NOT NULL,
 created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS stories (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 slug TEXT UNIQUE NOT NULL,
 title TEXT NOT NULL,
 subtitle TEXT,
 description TEXT,
 active INTEGER DEFAULT 1
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS games (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 code TEXT UNIQUE NOT NULL,
 story_id INTEGER NOT NULL,
 duration INTEGER DEFAULT 30,
 difficulty TEXT DEFAULT 'normal',
 mode TEXT DEFAULT 'local',
 status TEXT DEFAULT 'waiting',
 current_stage INTEGER DEFAULT 0,
 started_at TEXT NULL,
 ends_at TEXT NULL,
 created_at TEXT DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(story_id) REFERENCES stories(id)
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS players (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 game_id INTEGER NOT NULL,
 name TEXT NOT NULL,
 joined_at TEXT DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(game_id) REFERENCES games(id) ON DELETE CASCADE
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS game_state (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 game_id INTEGER NOT NULL,
 state_key TEXT NOT NULL,
 state_value TEXT,
 UNIQUE(game_id,state_key),
 FOREIGN KEY(game_id) REFERENCES games(id) ON DELETE CASCADE
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS logs (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 game_id INTEGER,
 event TEXT,
 payload TEXT,
 created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");
$hash = password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT);
$pdo->prepare("INSERT OR IGNORE INTO admins(username,password_hash) VALUES(?,?)")->execute([DEFAULT_ADMIN_USER,$hash]);
$pdo->prepare("INSERT OR IGNORE INTO stories(slug,title,subtitle,description) VALUES(?,?,?,?)")->execute([
 'retour-vers-le-futur','RETOUR VERS LE FUTUR','Mission : restaurer la ligne temporelle',
 'Une anomalie temporelle menace la soirée. Les joueurs doivent traverser cinq épreuves et relancer la machine avant la rupture.'
]);
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Installation RT EVENTS</title><style>
body{font-family:Arial;background:#080b12;color:#fff;display:grid;place-items:center;min-height:100vh}.box{max-width:650px;padding:40px;border:1px solid #26334d;border-radius:18px;background:#101624}code{background:#05070b;padding:4px 8px;border-radius:6px}.ok{color:#71e6a2}
</style></head><body><div class="box"><h1>RT EVENTS — Installation</h1><p class="ok">✓ Base SQLite initialisée.</p><p>Compte administrateur : <code>admin</code></p><p>Mot de passe initial : <code>ChangeMe!2026</code></p><p><strong>Change le mot de passe dès que possible</strong> dans le fichier de configuration ou via le système d’authentification que nous ajouterons.</p><p><a href="/admin/login.php">Accéder à l'administration</a></p></div></body></html>