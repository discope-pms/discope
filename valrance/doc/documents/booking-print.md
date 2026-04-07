# Impression de la réservation

L'impression de réservation repose sur un provider spécifique qui prépare un document PDF ou HTML à partir d'une réservation.

## Particularités fonctionnelles

Le provider enrichit le document avec :

- l'identité client et les contacts ;
- les données du centre ;
- les groupes de lignes et consommations ;
- une présentation des tranches d'âges ;
- le mode d'affichage des consommations selon `booking_schedule_layout`.

## Mise en forme des consommations

Le code prévoit une variable `consumptions_type` alimentée à partir du type de réservation.

Cela permet d'adapter la présentation selon le paramétrage fonctionnel de la réservation, par exemple entre une vue simple et une vue plus détaillée.

## Coordonnées centre ou bureau

Quand `use_office_details` est activé sur le centre, les informations du bureau associé (`center_office_id`) sont utilisées dans le document.

Cette logique permet de distinguer :

- les coordonnées opérationnelles du centre ;
- les coordonnées administratives à afficher sur le document.

## Blocs documentaires

Le provider récupère aussi des `TemplatePart` pour injecter certains contenus variables, notamment :

- `advantage_notice`
- `tbc_notice`

Ces blocs permettent d'ajouter des mentions standardisées sans figer leur contenu dans le code.
