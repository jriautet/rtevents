# RT EVENTS — ESCAPE

Base complète pour héberger le portail Escape Game directement sous `rtevents.eu`.

## Architecture

- `/` — landing page RT EVENTS
- `/game/login.php` — accès joueur par code
- `/game/play.php` — moteur de jeu / interface joueur
- `/admin/login.php` — connexion administrateur
- `/admin/index.php` — préparation des parties
- `/admin/game.php` — contrôle d'une partie
- `/install/install.php` — initialisation SQLite

## Installation Plesk

1. Créer/ouvrir le site `rtevents.eu`.
2. Envoyer tout le contenu du dossier dans le document root.
3. Vérifier que PHP 8.1+ est actif avec l'extension SQLite (`pdo_sqlite`).
4. Ouvrir `/install/install.php` une seule fois.
5. Se connecter à `/admin/login.php`.
6. Identifiant initial : `admin`
7. Mot de passe initial : `ChangeMe!2026`
8. Supprimer ou protéger le dossier `/install` après installation.

## Fonctionnement

L'administrateur crée une partie :
- scénario
- durée
- difficulté
- mode
- code unique généré automatiquement

Les joueurs entrent le code et leur prénom. Ils rejoignent alors la partie.

Le moteur est volontairement organisé par scénario afin d'ajouter ensuite :
- d'autres histoires
- variantes A/B/C
- épreuves supplémentaires
- modes connecté
- rôles individuels
- système d'indices
- sons / animations
- statistiques

## Important

Cette première version fournit l'ossature fonctionnelle complète et le premier gameplay de démonstration. Le scénario Retour vers le Futur doit ensuite être enrichi avec les 5 vraies épreuves interactives et le système de synchronisation temps réel pour obtenir la version événementielle finale.
