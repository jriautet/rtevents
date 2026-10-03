<?php
require_once __DIR__.'/../config/config.php';
$code=strtoupper(trim($_GET['code']??''));
$joinUrl=APP_URL.'/game/login.php?code='.rawurlencode($code);
$qr='https://api.qrserver.com/v1/create-qr-code/?size=420x420&margin=10&data='.rawurlencode($joinUrl);
?><!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#02060a"><title>RT ESCAPE · ÉCRAN SALLE</title><link rel="stylesheet" href="/assets/app.css"></head><body class="room-screen room-control-screen"><div id="screen"></div><script>
const code=<?=json_encode($code)?>,joinUrl=<?=json_encode($joinUrl)?>,qrUrl=<?=json_encode($qr)?>;
let lastMediaId=null,lastCrewHash='';
const esc=s=>String(s??'').replace(/[&<>\"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
function online(x){return !!x.online}
function crewHash(crew){return JSON.stringify((crew||[]).map(x=>[x.id,x.name,x.online,x.ready,x.role_assigned]));}
function renderBase(g){
 const crew=g.crew||[], onlineCount=crew.filter(online).length;
 const pills=crew.length?crew.map(x=>`<span class="room-player-pill ${online(x)?'online':'offline'} ${Number(x.ready)?'ready':''}"><i></i><b>${esc(x.name)}</b><small>${Number(x.ready)?'EN PLACE':online(x)?'CONNECTÉ':'EN ATTENTE'}</small></span>`).join(''):`<span class="room-no-player">En attente des joueurs…</span>`;
 return `<main class="room-idle">
   <div class="room-hero"></div><div class="room-vignette"></div>
   <div class="room-connection">
      <div class="room-connection-title"><span>ACCÈS À LA MISSION</span><b>SCANNER POUR REJOINDRE</b></div>
      <div class="room-qr-wrap"><img src="${qrUrl}" alt="QR Code pour rejoindre RT ESCAPE" class="room-qr"><div class="room-qr-glow"></div></div>
      <div class="room-join-label">CODE DE PARTIE</div><div class="room-join-code">${esc(code||'------')}</div>
      <div class="room-join-url">${esc(joinUrl)}</div>
   </div>
   <div class="room-live-status"><span class="live-dot"></span><b>${onlineCount} JOUEUR${onlineCount>1?'S':''} CONNECTÉ${onlineCount>1?'S':''}</b><small>La régie maître du jeu est prête</small></div>
   <div class="room-crew-dock"><div class="room-crew-caption">ÉQUIPAGE EN DIRECT</div><div class="room-crew-pills">${pills}</div></div>
   <div class="room-screen-mark">ÉCRAN SALLE · RT ESCAPE · RT EVENTS</div>
 </main>`;
}
function renderMedia(g){const m=g.room_media;if(!m)return '';
 if(m.type==='image'&&m.media_url)return `<div class="room-media-overlay room-media-live"><div class="room-media-frame"><img src="${esc(m.media_url)}" alt=""><div class="room-media-caption"><span>TRANSMISSION DU MAÎTRE DU JEU</span><h1>${esc(m.title||'')}</h1><p>${esc(m.body||'')}</p></div></div></div>`;
 if(m.type==='video'&&m.media_url)return `<div class="room-media-overlay room-media-live"><div class="room-video-frame"><video src="${esc(m.media_url)}" autoplay playsinline controls></video><div class="room-media-caption"><span>TRANSMISSION DU MAÎTRE DU JEU</span><h1>${esc(m.title||'')}</h1><p>${esc(m.body||'')}</p></div></div></div>`;
 return `<div class="room-media-overlay room-media-live"><div class="room-command-frame"><span>TRANSMISSION DU MAÎTRE DU JEU</span><h1>${esc(m.title||'MESSAGE')}</h1><p>${esc(m.body||'')}</p></div></div>`;
}
async function poll(){try{const r=await fetch('/api/public_state.php?code='+encodeURIComponent(code),{cache:'no-store'});if(!r.ok)return;const d=await r.json();if(!d.ok)return;const g=d.game||{};const h=crewHash(g.crew||[]);const mediaId=g.room_media?.id||null;if(h!==lastCrewHash||mediaId!==lastMediaId){lastCrewHash=h;lastMediaId=mediaId;document.getElementById('screen').innerHTML=renderBase(g)+renderMedia(g);if(mediaId&&g.room_media?.type==='video'){const v=document.querySelector('.room-video-frame video');if(v){v.play().catch(()=>{});}}}}catch(e){}}
poll();setInterval(poll,1000);
</script></body></html>
