<?php
require_once __DIR__.'/../config/config.php'; start_app_session();
if(empty($_SESSION['player_game_id'])) redirect('/game/login.php');
$st=db()->prepare("SELECT g.*,s.title,s.subtitle,s.slug FROM games g JOIN stories s ON s.id=g.story_id WHERE g.id=?"); $st->execute([$_SESSION['player_game_id']]); $game=$st->fetch(PDO::FETCH_ASSOC);
if(!$game) redirect('/game/login.php');
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($game['title'])?> — RT ESCAPE</title><link rel="stylesheet" href="/assets/app.css"></head>
<body class="game-ui"><header class="game-top"><div class="game-logo">RT <span>ESCAPE</span></div><div class="mission">MISSION <strong id="stage">01</strong> / 05</div><div class="timer" id="timer">--:--</div></header>
<main class="game-main"><section class="scientist"><div class="avatar">⚡</div><div><span class="eyebrow">TRANSMISSION ENTRANTE</span><h1 id="title">ANOMALIE TEMPORELLE DÉTECTÉE</h1><p id="story">La ligne temporelle est instable. Le professeur vous attend dans son laboratoire. Votre équipe doit restaurer la chronologie avant la rupture.</p></div></section>
<section class="game-panel"><div class="progress"><i></i></div><div id="mission-content" class="mission-content"><h2>Le laboratoire</h2><p>Explorez les éléments du laboratoire et trouvez la séquence temporelle correcte.</p><div class="objects"><button onclick="inspect('clock')">HORLOGE</button><button onclick="inspect('files')">DOSSIERS</button><button onclick="inspect('radio')">RADIO</button><button onclick="inspect('console')">CONSOLE</button></div><div id="result" class="result"></div></div></section></main>
<script>
const gameId=<?=json_encode((int)$game['id'])?>;
let timerSeconds=<?=json_encode($game['duration']*60)?>, started=<?=json_encode($game['status']==='running')?>, stage=<?=json_encode((int)$game['current_stage'])?>;
function renderTimer(){let m=Math.floor(timerSeconds/60),s=timerSeconds%60;document.getElementById('timer').textContent=String(m).padStart(2,'0')+':'+String(s).padStart(2,'0')}
function inspect(k){const data={clock:'L’horloge indique 10:04. Un chiffre attire votre attention : 88.',files:'Un dossier mentionne une puissance impossible : 1.21 GW.',radio:'La radio grésille : trois impulsions, puis deux.',console:'La console demande une séquence de validation. Les indices semblent converger.'};document.getElementById('result').innerHTML='<strong>INDICE</strong><br>'+data[k]}
setInterval(()=>{if(started&&timerSeconds>0){timerSeconds--;renderTimer()}},1000);renderTimer();
</script></body></html>