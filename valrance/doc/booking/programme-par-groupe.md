# Programme par groupe

Le programme par groupe d'une réservation génère un document PDF qui affiche les plannings d'activités pour chaque groupe avec leur détail.

## Où le trouver

`Apps dashboard -> Réservations -> Fiche réservation -> Programme par groupe`

Le document est accessible depuis le menu de droite de la réservation.

## Usage

Ce document est destiné à présenter, pour une réservation, le déroulé opérationnel groupe par groupe.

Il est particulièrement utile lorsque :

- plusieurs groupes coexistent dans un même séjour ;
- les activités sont réparties sur plusieurs journées ;
- l'équipe terrain a besoin d'un support synthétique à remettre ou imprimer.

## Commentaires premier et dernier jour

Le package prévoit l'injection de commentaires à des moments précis de la journée au travers du modèle `RV.planning.activity.doc`.

Les clés mentionnées dans la documentation métier sont :

- `first_day_am_comment`
- `first_day_pm_comment`
- `first_day_ev_comment`
- `last_day_am_comment`
- `last_day_pm_comment`
- `last_day_ev_comment`

Exemple d'usage :

- rappeler la libération des chambres le dernier matin ;
- préciser une consigne d'accueil ou de départ ;
- ajouter une consigne logistique sur une tranche horaire donnée.

## Intérêt métier

Cette personnalisation permet d'éviter des annotations manuelles sur les programmes transmis aux équipes ou aux clients, tout en gardant un comportement piloté par template.
