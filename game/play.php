<?php
require_once __DIR__.'/../config/config.php'; start_app_session();
if(empty($_SESSION['player_game_id'])) redirect('/game/login.php');
$st=db()->prepare("SELECT g.*,s.title,s.subtitle,s.slug FROM games g JOIN stories s ON s.id=g.story_id WHERE g.id=?"); $st->execute([$_SESSION['player_game_id']]); $game=$st->fetch(PDO::FETCH_ASSOC); if(!$game) redirect('/game/login.php');
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($game['title'])?> — RT ESCAPE</title><link rel="stylesheet" href="/assets/app.css"></head>
<body class="game-ui"><header class="game-top"><div class="game-logo">RT <span>ESCAPE</span></div><div class="mission">ÉPREUVE <strong id="stage">01</strong> / 05</div><div class="timer-wrap"><small>ÉPREUVE</small><div class="timer" id="timer">--:--</div></div></header>
<main class="game-main"><section class="scientist"><div class="avatar" id="avatar">⚡</div><div><span class="eyebrow" id="eyebrow">TRANSMISSION ENTRANTE</span><h1 id="title">ANOMALIE TEMPORELLE DÉTECTÉE</h1><p id="story">La ligne temporelle est instable.</p></div></section>
<section class="game-panel"><div class="progress"><i id="progress"></i></div><div id="mission-content" class="mission-content"></div></section></main>
<div class="modal hidden" id="answerModal"><div class="answer-card"><span class="eyebrow">TEMPS ÉCOULÉ</span><h2>RÉPONSE OBLIGATOIRE</h2><p>La fenêtre temporelle se ferme. Vous devez maintenant donner votre réponse.</p><form id="answerForm"><input id="answer" autocomplete="off" placeholder="Votre réponse" required><button class="btn primary">VALIDER LA RÉPONSE</button></form><div id="answerMessage" class="answer-message"></div></div></div>
<div class="toast hidden" id="toast"></div>
<script>
let state=null, poll=null, lastStage=0, modalForced=false;
const esc=s=>{const d=document.createElement('div');d.textContent=s??'';return d.innerHTML};
function fmt(sec){sec=Math.max(0,Number(sec)||0);return String(Math.floor(sec/60)).padStart(2,'0')+':'+String(sec%60).padStart(2,'0')}
function toast(msg){const t=document.getElementById('toast');t.textContent=msg;t.classList.remove('hidden');setTimeout(()=>t.classList.add('hidden'),2600)}
async function fetchState(){const r=await fetch('/api/state.php',{cache:'no-store'});if(r.status===410){document.body.innerHTML='<main class="join"><section class="join-card"><span class="eyebrow">PARTIE TERMINÉE</span><h1>Vous avez été retiré</h1><p>L’organisateur vous a retiré de cette partie.</p><a class="btn primary" href="/game/login.php">REJOINDRE UNE AUTRE PARTIE</a></section></main>';clearInterval(poll);return;}if(!r.ok)return;const d=await r.json();if(!d.ok)return;state=d.game;render();}
function render(){
 if(!state)return; const stage=Number(state.stage); document.getElementById('stage').textContent=String(stage||1).padStart(2,'0');
 document.getElementById('timer').textContent=fmt(state.stage_remaining);
 document.getElementById('progress').style.width=(stage/5*100)+'%';
 document.getElementById('timer').classList.toggle('danger',state.stage_remaining<=60);
 if(stage!==lastStage){lastStage=stage;modalForced=false;}
 if(!stage){document.getElementById('title').textContent='EN ATTENTE DU DÉPART';document.getElementById('story').textContent='L’organisateur prépare la mission. Restez prêts…';document.getElementById('mission-content').innerHTML='<div class="waiting"><div class="pulse">◉</div><h2>La partie va commencer</h2><p>Votre équipe apparaîtra ici dès que l’organisateur lancera le chronomètre.</p></div>';return;}
 if(state.status==='finished' && stage===5 && state.response_value){document.getElementById('title').textContent='TIMELINE RESTORED';document.getElementById('story').textContent='La ligne temporelle est stabilisée.';document.getElementById('mission-content').innerHTML='<div class="finale"><div class="finale-icon">⚡</div><h2>MISSION ACCOMPLIE</h2><p>88 MPH · 1.21 GW · DESTINATION VALIDÉE</p><strong>BON ANNIVERSAIRE LÉANE !</strong></div>';return;}
 const m=state.mission;if(!m)return;
 document.getElementById('eyebrow').textContent=m.eyebrow;document.getElementById('title').textContent=m.title;document.getElementById('story').textContent=m.intro;
 const found=new Set(state.discoveries||[]);
 let html='';
 if(stage===1){
   html += renderLaboratory(m, found);
 } else {
   html='<div class="mission-head"><div><span class="eyebrow">EXPLOREZ</span><h2>Fouillez chaque élément</h2><p>Certains éléments sont indispensables. D’autres servent de fausses pistes.</p></div><div class="found">'+found.size+' découverte'+(found.size>1?'s':'')+'</div></div><div class="objects">';
   (m.objects||[]).forEach(o=>{html+=`<button class="object ${found.has(o.key)?'found':''}" onclick="inspect('${esc(o.key)}')"><span>${o.icon}</span><b>${esc(o.title)}</b><small>${found.has(o.key)?'DÉCOUVERT':'EXAMINER'}</small></button>`});
   html+='</div>';
 }
 if(state.hint) html+='<div class="hint-box"><strong>INDICE DU PROFESSEUR</strong><p>'+esc(state.hint)+'</p></div>';
 if(state.answer_revealed) html+='<div class="revealed-answer"><span>RÉPONSE RÉVÉLÉE PAR L'ORGANISATEUR</span><strong>'+esc(state.revealed_answer)+'</strong></div>';
 html+='<div id="result" class="result"></div>';
 if(state.response_required) html+='<button class="force-answer" onclick="openAnswer()">⚠️ DONNER LA RÉPONSE</button>';
 document.getElementById('mission-content').innerHTML=html;
 if(state.response_required&&!modalForced)openAnswer();
}
async function inspect(key){const body=new URLSearchParams({key,stage:String(state.stage)});const r=await fetch('/api/discover.php',{method:'POST',body});const d=await r.json();if(d.ok){state.discoveries=[...(state.discoveries||[]),d.key].filter((v,i,a)=>a.indexOf(v)===i);render();document.getElementById('result').innerHTML='<strong>'+esc(d.title)+'</strong><p>'+esc(d.text)+'</p><span class="clue">FRAGMENT : '+esc(d.clue)+'</span>';}}
function renderLaboratory(m, found){
 const has=k=>found.has(k)?' found':'';
 return `<div class="lab-wrap">
   <div class="lab-toolbar"><div><span class="eyebrow">SCÈNE INTERACTIVE · LABORATOIRE</span><h2>Fouillez le laboratoire</h2><p>Observez les objets, cliquez dessus et récupérez les fragments temporels.</p></div><div class="found lab-found">${found.size} / ${(m.objects||[]).length} objets examinés</div></div>
   <div class="lab-scene">
     <div class="lab-window"><span>HILL VALLEY · 1955 → 1985</span><i></i><i></i><i></i></div>
     <div class="lab-shelf shelf-a"><span>ARCHIVES</span><b></b><b></b><b></b><b></b></div>
     <div class="lab-shelf shelf-b"><span>INSTRUMENTS</span><b></b><b></b><b></b></div>
     <button class="lab-hotspot clock-hotspot${has('clock')}" onclick="inspect('clock')" aria-label="Examiner l'horloge"><img src="/assets/lab/clock.svg"><em>${found.has('clock')?'DÉCOUVERT':'HORLOGE · EXAMINER'}</em></button>
     <button class="lab-hotspot files-hotspot${has('files')}" onclick="inspect('files')" aria-label="Examiner les dossiers"><img src="/assets/lab/files.svg"><em>${found.has('files')?'DÉCOUVERT':'DOSSIERS · EXAMINER'}</em></button>
     <button class="lab-hotspot radio-hotspot${has('radio')}" onclick="inspect('radio')" aria-label="Examiner la radio"><img src="/assets/lab/radio.svg"><em>${found.has('radio')?'DÉCOUVERT':'RADIO · EXAMINER'}</em></button>
     <button class="lab-hotspot console-hotspot${has('console')}" onclick="inspect('console')" aria-label="Examiner la console"><img src="/assets/lab/console.svg"><em>${found.has('console')?'DÉCOUVERT':'CONSOLE · EXAMINER'}</em></button>
     <div class="lab-desk"><div class="desk-light"></div><div class="paper-stack"></div><div class="desk-label">TEMPORAL RESEARCH UNIT · 01</div></div>
     <div class="lab-floor"></div>
     <div class="lab-vignette"></div>
   </div>
   <div class="lab-legend"><span><i class="dot pulse-dot"></i> Objet interactif</span><span><i class="dot found-dot"></i> Objet déjà fouillé</span><span>💡 Chaque objet peut contenir un fragment… ou une fausse piste.</span></div>
 </div>`;
}
function openAnswer(){document.getElementById('answerModal').classList.remove('hidden');document.getElementById('answer').focus();modalForced=true}
document.getElementById('answerForm').addEventListener('submit',async e=>{e.preventDefault();const answer=document.getElementById('answer').value.trim();if(!answer)return;const body=new URLSearchParams({stage:String(state.stage),answer});const r=await fetch('/api/answer.php',{method:'POST',body});const d=await r.json();const box=document.getElementById('answerMessage');box.textContent=d.message||'';if(d.correct){document.getElementById('answerModal').classList.add('hidden');toast(d.finished?'TIMELINE RESTORED !':'MISSION VALIDÉE !');modalForced=false;await fetchState();}else{box.className='answer-message error';}});
fetchState();poll=setInterval(fetchState,1500);
</script></body></html>
