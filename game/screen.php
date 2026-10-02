<?php
require_once __DIR__.'/../config/config.php'; start_app_session();
$code=strtoupper(trim($_GET['code']??''));
?><!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>RT ESCAPE · ÉCRAN DE SALLE</title><link rel="stylesheet" href="/assets/app.css"></head><body class="room-screen"><div id="screen"></div><script>
const code=<?=json_encode($code)?>;let lastStage=0,lastSolved=false;function esc(s){return String(s??'').replace(/[&<>\"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));}function fmt(s){s=Math.max(0,Number(s)||0);return String(Math.floor(s/60)).padStart(2,'0')+':'+String(s%60).padStart(2,'0')}
async function poll(){const r=await fetch('/api/public_state.php?code='+encodeURIComponent(code),{cache:'no-store'});if(!r.ok)return;const d=await r.json();if(d.ok)render(d.game)}
function render(g){
 const m=g.mission||{},p=g.puzzle||{},crew=g.crew||[],solved=g.puzzle_solved,stage=Number(g.stage||0);
 if(stage!==lastStage){lastStage=stage;document.body.className='room-screen stage-'+stage;}
 let html='<div class="screen-top"><div class="screen-brand">RT <span>ESCAPE</span></div><div>HILL VALLEY · 1985</div><div class="screen-timer">'+fmt(g.stage_remaining)+'</div></div>';
 if(!stage){
   const ready=crew.filter(x=>Number(x.ready)===1).length;
   html+='<div class="screen-boarding"><div class="screen-boarding-title"><span class="screen-kicker">DELOREAN DMC-12 · TEMPORAL RESEARCH VEHICLE</span><h1>PRENEZ PLACE</h1><p>L’équipage doit être installé avant l’activation du Flux Capacitor.</p></div><div class="screen-car"><div class="screen-windshield">HILL VALLEY · 1985</div><div class="screen-dashboard"><div class="screen-gauge">0<br><small>MPH</small></div><div class="screen-flux-core">⚡<small>FLUX CAPACITOR</small><b>OFFLINE</b></div><div class="screen-gauge small-gauge">21:00</div></div></div><div class="crew-board">';
   crew.forEach((x,i)=>{html+='<div class="crew-seat '+(Number(x.ready)?'ready':'')+'"><span>'+(Number(x.ready)?'✓':'○')+'</span><div><b>'+esc(x.name)+'</b><small>'+esc(x.seat||'POSTE TEMPORAIRE')+'</small></div></div>';});
   html+='</div><div class="screen-ready-count">ÉQUIPAGE EN PLACE · '+ready+' / '+crew.length+'</div></div>';
 }else if(g.status==='finished'){
   html+='<div class="screen-center finale-screen"><div class="screen-flux">⚡</div><div class="screen-kicker">TIMELINE RESTORED</div><h1>MISSION ACCOMPLIE</h1><p>88 MPH · 1.21 GW · HILL VALLEY 1985</p></div>';
 }else if(stage===1 && !solved){
   html+='<div class="screen-delorean"><div class="delorean-windshield"><span>HILL VALLEY</span><b>1985</b><small>FLUX CAPACITOR · OFFLINE</small></div><div class="delorean-dash"><div class="screen-gauge large">0<small>MPH</small></div><div class="screen-flux-core live">⚡<small>FLUX CAPACITOR</small><b>STANDBY</b></div><div class="screen-controls"><span>TIME CIRCUIT</span><strong>-- / -- / ----</strong><strong>--:--</strong></div></div><div class="screen-narrative"><span class="screen-kicker">ÉPREUVE 01 · ET SI NOUS REMONTIONS LE TEMPS ?</span><h1>LA DELOREAN ATTEND SON ÉQUIPAGE</h1><p>Écoutez le maître du jeu. Observez vos postes. Les informations sont réparties entre vous.</p><div class="screen-progress"><i style="width:'+Math.min(100,stage/5*100)+'%"></i></div></div></div>';
 }else if(solved){
   html+='<div class="screen-center solved-screen"><div class="screen-flux">✓</div><div class="screen-kicker">ÉPREUVE '+String(stage).padStart(2,'0')+' VALIDÉE</div><h1>LA TIMELINE SE STABILISE</h1><p>L’ORGANISATEUR PRÉPARE LA SUITE…</p></div>';
 }else{
   html+='<div class="screen-center"><div class="screen-kicker">ÉPREUVE '+String(stage).padStart(2,'0')+' / 05</div><h1>'+esc(m.title)+'</h1><p>'+esc(m.intro)+'</p><div class="screen-progress"><i style="width:'+Math.min(100,stage/5*100)+'%"></i></div></div>';
 }
 document.getElementById('screen').innerHTML=html;
}
poll();setInterval(poll,1000);
</script></body></html>
