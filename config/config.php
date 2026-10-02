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
        ensure_schema($pdo);
    }
    return $pdo;
}

function ensure_schema(PDO $pdo): void {
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
        variant TEXT DEFAULT 'A',
        status TEXT DEFAULT 'waiting',
        current_stage INTEGER DEFAULT 0,
        started_at TEXT NULL,
        ends_at TEXT NULL,
        stage_started_at TEXT NULL,
        stage_ends_at TEXT NULL,
        response_required INTEGER DEFAULT 0,
        response_value TEXT NULL,
        completed_at TEXT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(story_id) REFERENCES stories(id)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS players (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        game_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        player_token TEXT UNIQUE,
        joined_at TEXT DEFAULT CURRENT_TIMESTAMP,
        last_seen_at TEXT DEFAULT CURRENT_TIMESTAMP,
        seat TEXT NULL,
        role_key TEXT NULL,
        ready INTEGER DEFAULT 0,
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
    $pdo->exec("CREATE TABLE IF NOT EXISTS discoveries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        game_id INTEGER NOT NULL,
        player_id INTEGER NULL,
        stage INTEGER NOT NULL,
        discovery_key TEXT NOT NULL,
        content TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(game_id,player_id,stage,discovery_key),
        FOREIGN KEY(game_id) REFERENCES games(id) ON DELETE CASCADE,
        FOREIGN KEY(player_id) REFERENCES players(id) ON DELETE CASCADE
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        game_id INTEGER,
        event TEXT,
        payload TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");

    // Migration douce pour les anciennes bases créées avec la V1.
    $cols = [];
    foreach ($pdo->query("PRAGMA table_info(games)")->fetchAll(PDO::FETCH_ASSOC) as $c) $cols[$c['name']] = true;
    $add = [
        'variant' => "ALTER TABLE games ADD COLUMN variant TEXT DEFAULT 'A'",
        'stage_started_at' => "ALTER TABLE games ADD COLUMN stage_started_at TEXT NULL",
        'stage_ends_at' => "ALTER TABLE games ADD COLUMN stage_ends_at TEXT NULL",
        'response_required' => "ALTER TABLE games ADD COLUMN response_required INTEGER DEFAULT 0",
        'response_value' => "ALTER TABLE games ADD COLUMN response_value TEXT NULL",
        'completed_at' => "ALTER TABLE games ADD COLUMN completed_at TEXT NULL",
    ];
    foreach ($add as $name => $sql) if (!isset($cols[$name])) $pdo->exec($sql);

    $pcols = [];
    foreach ($pdo->query("PRAGMA table_info(players)")->fetchAll(PDO::FETCH_ASSOC) as $c) $pcols[$c['name']] = true;
    if (!isset($pcols['player_token'])) $pdo->exec("ALTER TABLE players ADD COLUMN player_token TEXT");
    if (!isset($pcols['seat'])) $pdo->exec("ALTER TABLE players ADD COLUMN seat TEXT NULL");
    if (!isset($pcols['role_key'])) $pdo->exec("ALTER TABLE players ADD COLUMN role_key TEXT NULL");
    if (!isset($pcols['ready'])) $pdo->exec("ALTER TABLE players ADD COLUMN ready INTEGER DEFAULT 0");
    if (!isset($pcols['last_seen_at'])) {
        // SQLite interdit DEFAULT CURRENT_TIMESTAMP lors d'un ALTER TABLE ADD COLUMN.
        $pdo->exec("ALTER TABLE players ADD COLUMN last_seen_at TEXT");
        $pdo->exec("UPDATE players SET last_seen_at = CURRENT_TIMESTAMP WHERE last_seen_at IS NULL");
    }

    $hash = password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT);
    $pdo->prepare("INSERT OR IGNORE INTO admins(username,password_hash) VALUES(?,?)")->execute([DEFAULT_ADMIN_USER,$hash]);
    $pdo->prepare("INSERT OR IGNORE INTO stories(slug,title,subtitle,description) VALUES(?,?,?,?)")->execute([
        'retour-vers-le-futur','RETOUR VERS LE FUTUR','Mission : restaurer la ligne temporelle',
        'Une anomalie temporelle menace la soirée. Les joueurs doivent traverser cinq épreuves et relancer la machine avant la rupture.'
    ]);
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
function now_utc(): DateTimeImmutable { return new DateTimeImmutable('now', new DateTimeZone('UTC')); }
function iso_now(): string { return now_utc()->format('Y-m-d H:i:s'); }
function game_duration_seconds(array $game): int { return max(60, (int)$game['duration'] * 60); }
function stage_duration_seconds(array $game): int { return (int)ceil(game_duration_seconds($game) / 5); }
function log_game(int $gameId, string $event, array $payload=[]): void {
    db()->prepare("INSERT INTO logs(game_id,event,payload) VALUES(?,?,?)")->execute([$gameId,$event,json_encode($payload,JSON_UNESCAPED_UNICODE)]);
}
function set_state(int $gameId,string $key,string $value): void {
    db()->prepare("INSERT INTO game_state(game_id,state_key,state_value) VALUES(?,?,?) ON CONFLICT(game_id,state_key) DO UPDATE SET state_value=excluded.state_value")->execute([$gameId,$key,$value]);
}
function get_state(int $gameId,string $key,?string $default=null): ?string {
    $s=db()->prepare("SELECT state_value FROM game_state WHERE game_id=? AND state_key=?"); $s->execute([$gameId,$key]); $v=$s->fetchColumn(); return $v===false?$default:(string)$v;
}
function answer_revealed(int $gameId,int $stage): bool { return get_state($gameId,'answer_revealed_stage_'.$stage,'0') === '1'; }

function player_roles_for_count(int $count): array {
    $sets = [
        1 => [['seat'=>'CONDUCTEUR','role_key'=>'driver','label'=>'LE CONDUCTEUR','secret'=>'Tu conduis la DeLorean. Tu es le seul à pouvoir déclencher le départ. Observe bien le tableau de bord.']],
        2 => [
            ['seat'=>'CONDUCTEUR','role_key'=>'driver','label'=>'LE CONDUCTEUR','secret'=>'Tu conduis la DeLorean. Tu es responsable du départ.'],
            ['seat'=>'PASSAGER','role_key'=>'chrononaut','label'=>'LE CHRONONAUTE','secret'=>'Tu surveilles les anomalies temporelles et les informations affichées par les instruments.'],
        ],
        3 => [
            ['seat'=>'CONDUCTEUR','role_key'=>'driver','label'=>'LE CONDUCTEUR','secret'=>'Tu conduis la DeLorean. Ton tableau de bord contient une information que les autres n’ont pas.'],
            ['seat'=>'PASSAGER GAUCHE','role_key'=>'archivist','label'=>'L’ARCHIVISTE','secret'=>'Tu connais les dates et les événements. Ta mémoire de Hill Valley sera indispensable.'],
            ['seat'=>'PASSAGER DROIT','role_key'=>'technician','label'=>'LE TECHNICIEN','secret'=>'Tu comprends les systèmes de la machine. Certaines commandes te sont destinées.'],
        ],
        4 => [
            ['seat'=>'CONDUCTEUR','role_key'=>'driver','label'=>'LE CONDUCTEUR','secret'=>'Tu conduis la DeLorean. Tu dois écouter les autres mais tu es responsable du départ.'],
            ['seat'=>'PASSAGER AVANT','role_key'=>'chrononaut','label'=>'LE CHRONONAUTE','secret'=>'Tu surveilles les coordonnées temporelles. Tu recevras des informations que les autres ne voient pas.'],
            ['seat'=>'PASSAGER ARRIÈRE GAUCHE','role_key'=>'archivist','label'=>'L’ARCHIVISTE','secret'=>'Tu connais les dates et les événements. Certaines informations historiques sont cachées pour toi.'],
            ['seat'=>'PASSAGER ARRIÈRE DROIT','role_key'=>'technician','label'=>'LE TECHNICIEN','secret'=>'Tu es le spécialiste de la machine. Tu sais reconnaître les systèmes à remettre sous tension.'],
        ],
        5 => [
            ['seat'=>'CONDUCTEUR','role_key'=>'driver','label'=>'LE CONDUCTEUR','secret'=>'Tu conduis la DeLorean et contrôles le départ.'],
            ['seat'=>'PASSAGER AVANT','role_key'=>'chrononaut','label'=>'LE CHRONONAUTE','secret'=>'Tu surveilles les coordonnées temporelles.'],
            ['seat'=>'PASSAGER ARRIÈRE GAUCHE','role_key'=>'archivist','label'=>'L’ARCHIVISTE','secret'=>'Tu connais les dates et les événements de Hill Valley.'],
            ['seat'=>'PASSAGER ARRIÈRE CENTRE','role_key'=>'observer','label'=>'L’OBSERVATEUR','secret'=>'Tu remarques les détails étranges et les indices dissimulés dans la scène.'],
            ['seat'=>'PASSAGER ARRIÈRE DROIT','role_key'=>'technician','label'=>'LE TECHNICIEN','secret'=>'Tu maîtrises les systèmes électriques et le Flux Capacitor.'],
        ],
        6 => [
            ['seat'=>'CONDUCTEUR','role_key'=>'driver','label'=>'LE CONDUCTEUR','secret'=>'Tu conduis la DeLorean et contrôles le départ.'],
            ['seat'=>'PASSAGER AVANT','role_key'=>'chrononaut','label'=>'LE CHRONONAUTE','secret'=>'Tu surveilles les coordonnées temporelles.'],
            ['seat'=>'PASSAGER ARRIÈRE GAUCHE','role_key'=>'archivist','label'=>'L’ARCHIVISTE','secret'=>'Tu connais les dates et les événements de Hill Valley.'],
            ['seat'=>'PASSAGER ARRIÈRE CENTRE','role_key'=>'observer','label'=>'L’OBSERVATEUR','secret'=>'Tu remarques les détails étranges et les indices dissimulés dans la scène.'],
            ['seat'=>'PASSAGER ARRIÈRE DROIT','role_key'=>'technician','label'=>'LE TECHNICIEN','secret'=>'Tu maîtrises les systèmes électriques et le Flux Capacitor.'],
            ['seat'=>'NAVIGATEUR','role_key'=>'navigator','label'=>'LE NAVIGATEUR','secret'=>'Tu surveilles la destination et les coordonnées du voyage.'],
        ],
    ];
    return $sets[max(1,min(6,$count))];
}
function rebalance_player_roles(int $gameId): void {
    $q=db()->prepare("SELECT id FROM players WHERE game_id=? ORDER BY id"); $q->execute([$gameId]); $ids=array_column($q->fetchAll(PDO::FETCH_ASSOC),'id');
    $roles=player_roles_for_count(count($ids));
    $up=db()->prepare("UPDATE players SET seat=?,role_key=? WHERE id=?");
    foreach($ids as $i=>$pid){$r=$roles[$i]??$roles[count($roles)-1];$up->execute([$r['seat'],$r['role_key'],$pid]);}
}
function all_players_ready(int $gameId): bool {
    $q=db()->prepare("SELECT COUNT(*) total, COALESCE(SUM(CASE WHEN ready=1 THEN 1 ELSE 0 END),0) ready FROM players WHERE game_id=?");
    $q->execute([$gameId]); $r=$q->fetch(PDO::FETCH_ASSOC); return (int)$r['total']>0 && (int)$r['total']===(int)$r['ready'];
}
function game_content(string $slug): array {
    if ($slug !== 'retour-vers-le-futur') return [];
    return [
        1 => [
            'title'=>'LE LABORATOIRE',
            'eyebrow'=>'ÉPREUVE 01 · RECHERCHE',
            'intro'=>'Le laboratoire est en désordre. Quatre instruments contiennent des fragments de la séquence temporelle. Fouillez-les et reconstituez l’ordre avant la fermeture du laboratoire.',
            'answer'=>'8812104',
            'answer_hint'=>'La séquence finale rassemble les indices 88, 12, 10 et 4.',
            'objects'=>[
                ['key'=>'clock','icon'=>'🕐','title'=>'HORLOGE','text'=>'L’aiguille saute à 10:04. Sur le cadran, le nombre 88 est entouré deux fois.','clue'=>'88 · 10:04'],
                ['key'=>'files','icon'=>'📁','title'=>'DOSSIERS','text'=>'Un rapport scientifique mentionne : « la puissance doit être stabilisée à 1.21 GW ». Une page est marquée 12.','clue'=>'12'],
                ['key'=>'radio','icon'=>'📻','title'=>'RADIO','text'=>'La radio grésille puis affiche 04. Une voix enregistrée répète : « quatre… quatre… »','clue'=>'04'],
                ['key'=>'console','icon'=>'💻','title'=>'CONSOLE','text'=>'La console révèle un fragment caché : « Le départ est toujours lié à 88 ».','clue'=>'88'],
            ],
        ],
        2 => [
            'title'=>'LES ARCHIVES DE HILL VALLEY','eyebrow'=>'ÉPREUVE 02 · OBSERVATION',
            'intro'=>'Trois événements ont été déplacés dans les archives. Identifiez l’anomalie chronologique et donnez la date correcte.',
            'answer'=>'1985', 'answer_hint'=>'Le journal parle d’une voiture qui disparaît juste avant minuit. L’année est la clé.',
            'objects'=>[
                ['key'=>'newspaper','icon'=>'📰','title'=>'JOURNAL','text'=>'Une une annonce un événement daté de 1985.','clue'=>'1985'],
                ['key'=>'photo','icon'=>'📸','title'=>'PHOTO','text'=>'Une photo montre une scène qui ne devrait pas exister avant 1985.','clue'=>'1985'],
                ['key'=>'archive','icon'=>'🗃️','title'=>'ARCHIVE','text'=>'Le dossier porte le numéro 1955 mais une note indique « revenir 30 ans plus tard ».','clue'=>'30 ans'],
            ],
        ],
        3 => [
            'title'=>'LA RADIO TEMPORELLE','eyebrow'=>'ÉPREUVE 03 · MUSIQUE & LOGIQUE',
            'intro'=>'La radio reçoit un signal venu d’une autre époque. Retrouvez la fréquence et transformez-la en code.',
            'answer'=>'887', 'answer_hint'=>'Le signal commence par 88. La dernière impulsion est 7.',
            'objects'=>[
                ['key'=>'signal','icon'=>'📡','title'=>'SIGNAL','text'=>'Trois impulsions courtes, une longue, puis sept rapides. Le compteur affiche 88.','clue'=>'88 · 7'],
                ['key'=>'dial','icon'=>'🎚️','title'=>'FRÉQUENCE','text'=>'Le cadran est bloqué autour de 88 MHz. Une petite marque indique 7.','clue'=>'88.7'],
                ['key'=>'tape','icon'=>'🎞️','title'=>'BANDE','text'=>'Une vieille bande porte l’inscription : « le dernier chiffre compte ».','clue'=>'7'],
            ],
        ],
        4 => [
            'title'=>'LA DELOREAN','eyebrow'=>'ÉPREUVE 04 · MANIPULATION',
            'intro'=>'La machine est prête. Réglez destination, date et heure pour ouvrir le passage. Chaque paramètre doit être correct.',
            'answer'=>'1985102621', 'answer_hint'=>'Destination : 1985. Date : 26/10. Heure : 21h.',
            'objects'=>[
                ['key'=>'date','icon'=>'📅','title'=>'DATE','text'=>'Le cadran de destination indique 26 OCTOBRE.','clue'=>'26/10'],
                ['key'=>'year','icon'=>'⌛','title'=>'ANNÉE','text'=>'Le système exige l’année 1985.','clue'=>'1985'],
                ['key'=>'time','icon'=>'⏱️','title'=>'HEURE','text'=>'L’aiguille se fige à 21:00.','clue'=>'21'],
            ],
        ],
        5 => [
            'title'=>'1.21 GIGAWATTS','eyebrow'=>'ÉPREUVE 05 · FINALE',
            'intro'=>'La ligne temporelle s’effondre. Stabilisez le flux à 1.21 GW et atteignez 88 MPH. Cette fois, vous n’aurez qu’une seule réponse finale.',
            'answer'=>'12188', 'answer_hint'=>'La puissance est 1.21 GW et la vitesse cible est 88 MPH.',
            'objects'=>[
                ['key'=>'flux','icon'=>'⚡','title'=>'FLUX','text'=>'Le générateur réclame 1.21 GW.','clue'=>'121'],
                ['key'=>'speed','icon'=>'🚗','title'=>'VITESSE','text'=>'Le compteur rouge clignote à 88 MPH.','clue'=>'88'],
                ['key'=>'capacitor','icon'=>'🔋','title'=>'FLUX CAPACITOR','text'=>'Les deux valeurs doivent être saisies ensemble pour stabiliser la machine.','clue'=>'121 + 88'],
            ],
        ],
    ];
}
