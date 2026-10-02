# RT EVENTS — ESCAPE V2

Moteur web d'Escape Game hébergé sous `rtevents.eu`.

## Architecture

- `/` — présentation RT EVENTS
- `/game/login.php` — entrée joueur / code / QR
- `/game/play.php` — expérience joueur
- `/api/join.php` — connexion à une partie
- `/api/state.php` — état temps réel du jeu
- `/api/discover.php` — découverte d'un élément
- `/api/answer.php` — validation de réponse
- `/admin/login.php` — connexion organisateur
- `/admin/index.php` — création et liste des parties
- `/admin/game.php` — cockpit organisateur + QR code
- `/admin/game_action.php` — commandes de pilotage
- `/install/install.php` — installation / migration SQLite

## V2 — fonctionnement

### Organisateur

Depuis le back-office :

- choisir scénario
- choisir durée : 20 / 30 / 45 / 60 minutes
- choisir difficulté
- choisir mode local ou connecté
- choisir variante A/B/C
- générer un code de partie
- afficher un QR code de connexion
- voir les joueurs connectés
- démarrer / mettre en pause
- ajouter 5 minutes
- donner un indice
- imposer une réponse
- passer à l'épreuve suivante
- aller directement à une étape
- terminer ou réinitialiser la partie

### Joueurs

Le joueur scanne le QR code ou saisit le code, puis son prénom.

Le joueur ne possède aucun accès au back-office.

### Épreuves temporisées

Le temps total est réparti sur 5 épreuves. Chaque épreuve possède son propre chrono.

Quand le temps d'une épreuve arrive à zéro, le jeu passe en **RÉPONSE OBLIGATOIRE**. Les joueurs doivent alors proposer une réponse avant de pouvoir progresser.

Le temps total de la partie reste également surveillé.

### Gameplay Retour vers le Futur

Les 5 premières épreuves sont déjà structurées dans le moteur :

1. Le laboratoire — recherche
2. Les archives de Hill Valley — observation
3. La radio temporelle — musique / logique
4. La DeLorean — manipulation
5. 1.21 Gigawatts — finale

Chaque épreuve contient plusieurs objets interactifs à examiner. Les découvertes sont enregistrées et peuvent devenir individuelles en mode connecté.

## Installation Plesk

1. Envoyer le contenu du projet dans le document root.
2. Vérifier PHP 8.1+ et `pdo_sqlite`.
3. Ouvrir `/install/install.php` une fois après déploiement.
4. Se connecter à `/admin/login.php`.
5. Identifiant initial : `admin`
6. Mot de passe initial : `ChangeMe!2026`
7. Protéger ou supprimer `/install` après migration.

## Déploiement Git

Projet prévu pour :

```text
C:\Users\admin\Documents\rteventsfr
```

Puis :

```powershell
git add .
git commit -m "RT ESCAPE V2 - moteur interactif et cockpit"
git push origin main
```

Le dépôt utilisé est : `git@github.com:jriautet/rtevents.git`.
