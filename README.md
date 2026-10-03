# RT ESCAPE V9 — Maître du jeu / boîte aux lettres interactive

RT ESCAPE devient une régie d'escape game dématérialisée : le maître du jeu garde la main sur les joueurs, les messages, les documents, les indices et l'écran salle.

## Principe
- **Admin** : attribue les rôles et envoie librement consignes, énigmes, indices, images, vidéos et documents à un joueur ou à tout le groupe.
- **Téléphone joueur** : boîte aux lettres interactive avec réception des transmissions, demande d'indice et envoi d'une réponse au maître du jeu.
- **Écran salle** : le maître du jeu peut afficher texte, image ou vidéo à tout moment.
- **Rôles** : aucune action joueur ne peut modifier son rôle ; seul l'admin peut l'attribuer.
- **Dématérialisation** : les supports sont transmis numériquement et réutilisables.

Les anciennes données SQLite sont conservées ; le schéma s'enrichit automatiquement au premier chargement.


## Identité visuelle
Le logo officiel RT ESCAPE fourni par RT EVENTS est intégré dans la landing page, l’accès joueur, le cockpit maître du jeu, l’interface joueur et l’écran salle via `assets/rt-escape-logo.png`.

## V9.1 — mode live
- Les interfaces joueur et écran salle utilisent une connexion **Server-Sent Events (SSE)** persistante au lieu d'une requête toutes les 1 à 1,5 secondes.
- L'écran salle reste sur le hero tant qu'aucun média n'est actif ; une action du maître du jeu met à jour l'écran en direct.
- Le cockpit admin affiche les connexions / positions des joueurs en direct sans recharger automatiquement toute la page.
- Les messages de boîte aux lettres sont désormais rattachés à l'épreuve courante : les anciennes épreuves ne réapparaissent plus sur les téléphones lorsqu'on avance dans la partie.
- Le chronomètre joueur est interpolé localement entre les mises à jour serveur.
