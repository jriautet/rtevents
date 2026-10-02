# RT EVENTS — ESCAPE V5 · RETOUR VERS LE FUTUR

V5 recentre le moteur sur l'expérience de salle :

- partie connectée par QR code ;
- 1 à 6 joueurs ;
- attribution dynamique des sièges et identités ;
- chaque joueur confirme physiquement sa place via son téléphone ;
- quand tout l'équipage est prêt, la timeline démarre automatiquement ;
- messages privés par rôle ;
- écran salle dédié représentant la DeLorean ;
- épreuve 1 coopérative avec postes réservés aux rôles ;
- les joueurs doivent communiquer et manipuler plusieurs systèmes ;
- les rôles supplémentaires (observateur / navigateur) deviennent obligatoires à partir de 5/6 joueurs ;
- cockpit admin conservé : temps, indices, réponses, joueurs, suppression, écran salle ;
- SQLite conservée et migration douce.

## Déploiement

Ne pas supprimer `storage/escape.sqlite`.

```powershell
cd "C:\Users\admin\Documents\rteventsfr"
git add .
git commit -m "RT ESCAPE V5 - DeLorean connectee et experience de salle"
git push origin main
```

Puis déployer le dépôt dans Plesk.

## Architecture de l'expérience

- écran de salle / vidéoprojecteur : DeLorean, chronologie, animations ;
- téléphones : identité secrète, poste, actions personnelles ;
- organisateur : narration live, son, lumière, cockpit de pilotage.
