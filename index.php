<?php
require_once __DIR__.'/config/config.php';
start_app_session();
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>RT EVENTS — Nouvelle expérience</title><link rel="stylesheet" href="/assets/app.css"></head>
<body class="landing">
<header class="site-header"><a class="brand" href="/">RT <span>EVENTS</span></a><nav><a href="#experience">L'expérience</a><a href="#escape">Escape Game</a><a class="admin-link" href="/admin/login.php">Connexion admin</a></nav></header>
<main>
<section class="hero"><div class="hero-grid"></div><div class="hero-content"><div class="eyebrow">RT EVENTS · NOUVELLE EXPÉRIENCE</div><h1>On prépare quelque chose<br><strong>de nouveau.</strong></h1><p>RT EVENTS se refait une beauté et revient avec une nouvelle génération d'expériences événementielles.</p><div class="hero-actions"><a class="btn primary" href="/game/login.php">Accès Escape Game</a><a class="btn ghost" href="#experience">Découvrir</a></div></div><div class="hero-orbit"><span>RT</span><span>EVENTS</span></div></section>
<section id="experience" class="section"><div class="section-head"><span class="eyebrow">L'expérience</span><h2>Plus qu'un événement.<br><strong>Une expérience.</strong></h2></div><div class="cards"><article><b>01</b><h3>Immersif</h3><p>Une interface pensée comme un véritable jeu, avec progression, chronomètre et missions.</p></article><article><b>02</b><h3>Connecté</h3><p>Les participants rejoignent une partie grâce à un code généré à l'avance par l'organisateur.</p></article><article><b>03</b><h3>Évolutif</h3><p>Plusieurs scénarios, difficultés, durées et modes peuvent être ajoutés au même moteur.</p></article></div></section>
<section id="escape" class="escape-call"><div><span class="eyebrow">RT ESCAPE</span><h2>Votre partie commence ici.</h2><p>Vous avez reçu un code de connexion ? Rejoignez la partie.</p></div><a class="btn primary" href="/game/login.php">Entrer dans le jeu →</a></section>
</main><footer><span>© RT EVENTS</span><span>Une nouvelle expérience arrive prochainement.</span></footer></body></html>