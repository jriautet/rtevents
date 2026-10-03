# RT EVENTS — ESCAPE ENGINE V7

V7 ajoute un véritable **ÉCRAN SALLE générique** synchronisé avec les joueurs.

## Les 3 interfaces
- **Joueurs** : chaque joueur joue depuis son téléphone, avec ses informations et commandes privées.
- **Écran salle** : `/game/screen.php?code=XXXXXX` — TV / vidéoprojecteur. Il visualise l'état collectif, les énigmes et les éléments utiles à toute la salle sans révéler automatiquement les réponses.
- **Maître du jeu / admin** : contrôle la partie, les événements, les indices, les réponses et le passage des étapes.

Le principe reste **room-first** : la salle physique est le jeu, les téléphones sont les interfaces individuelles et l'écran est le support commun.

## Écran salle V7
L'écran s'adapte automatiquement à l'étape :
1. attente et mise en place de l'équipage ;
2. visualisation des archives et progression collective ;
3. console radio / fréquence / signal ;
4. time circuits / destination / séquence d'allumage ;
5. flux / puissance / vitesse / synchronisation finale.

Il fonctionne avec le endpoint public `/api/public_state.php` et ne nécessite aucune session joueur.
