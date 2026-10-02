<?php
require_once __DIR__ . '/../config/config.php';
// db() applique automatiquement la création/migration du schéma.
db();
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Installation RT ESCAPE</title><style>body{font-family:Arial;background:#080b12;color:#fff;display:grid;place-items:center;min-height:100vh}.box{max-width:720px;padding:40px;border:1px solid #26334d;border-radius:18px;background:#101624}.ok{color:#71e6a2}code{background:#05070b;padding:4px 8px;border-radius:6px}a{color:#b9d4ff}</style></head><body><div class="box"><h1>RT ESCAPE — Installation / Migration</h1><p class="ok">✓ Base SQLite initialisée ou mise à niveau.</p><p>Le moteur, les parties, les joueurs, les découvertes et le système d'épreuves temporisées sont prêts.</p><p>Compte administrateur : <code>admin</code></p><p>Mot de passe initial : <code>ChangeMe!2026</code></p><p><strong>À modifier dès que possible.</strong></p><p><a href="/admin/login.php">→ Accéder à l'administration</a></p></div></body></html>
