# RT ESCAPE V6 — ROOM FIRST

Cette version recentre l’expérience sur l’escape game en salle. L’écran de salle est un décor cinématique de DeLorean et le maître du jeu pilote les événements pendant sa narration. Les téléphones servent principalement à l’identité secrète et à la synchronisation des joueurs, pas à remplacer le jeu physique.

## Épreuve 1
1. Les joueurs rejoignent la partie.
2. Chaque joueur reçoit une place et une identité mystérieuse.
3. Ils prennent physiquement place dans la DeLorean de la salle.
4. Chaque joueur confirme « je suis en place ».
5. Quand tout le monde est prêt, la partie démarre.
6. Le maître du jeu raconte l’histoire en direct.
7. Depuis le cockpit admin, il déclenche les événements visuels de la DeLorean : histoire, lumières, moteur, anomalie, coordonnées, charge du Flux, saut temporel.
8. Les joueurs cherchent et coopèrent physiquement dans la salle.
9. Quand le maître du jeu veut demander la résolution, il peut forcer le terminal/réponse.

## Écran de salle
Ouvrir `/game/screen.php?code=XXXXXX` sur le PC/TV/vidéoprojecteur de la salle.

## Déploiement
Ne pas supprimer `storage/escape.sqlite`.
```powershell
cd "C:\Users\admin\Documents\rteventsfr"
git add .
git commit -m "RT ESCAPE V6 - room first DeLorean et mode maitre du jeu"
git push origin main
```
