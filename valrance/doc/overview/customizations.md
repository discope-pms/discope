# Personnalisations du package

## Logique générale

Le package Valrance illustre une stratégie de personnalisation légère à moyenne :

- ajout d'une app spécifique quand un domaine fonctionnel n'est utile que pour ce client ;
- surcharge des documents imprimés quand la mise en forme ou le contenu attendu diffèrent du standard ;
- ajout de vues de statistiques dédiées lorsque les exports attendus sont métier et non génériques.

## Application dédiée

Le manifeste du package déclare une app `valrance-stats` nommée **Statistiques**.

Cette app :

- est visible dans le dashboard ;
- réutilise les groupes d'accès statistiques standard ;
- injecte un menu latéral propre à Valrance.

## Vues et exports spécifiques

Le menu de statistiques Valrance expose notamment :

- `CA facturé`, `CA théorique`, `CA prévisionnel` en s'appuyant sur les stats standards ;
- `Contrats & Réservations` via un provider Valrance ;
- `Fiches récap.` via un provider Valrance.

Le package complète donc la couche statistique existante sans remplacer entièrement l'app de stats.

## Impressions personnalisées

Trois documents sont personnalisés :

- la réservation ;
- le contrat ;
- la facture.

Le package fournit à la fois :

- un provider PHP chargé de préparer les données ;
- un template HTML d'impression correspondant.

## Points de configuration visibles dans le code

Plusieurs comportements sont pilotés à partir de données déjà présentes dans Discope :

- `booking_schedule_layout` sur le type de réservation pour la présentation des consommations ;
- `use_office_details` et `center_office_id` pour choisir les coordonnées à afficher ;
- des `TemplatePart` pour injecter des blocs documentaires comme des notices ou clauses ;
- des règles comptables pour catégoriser le chiffre d'affaires dans les statistiques.
