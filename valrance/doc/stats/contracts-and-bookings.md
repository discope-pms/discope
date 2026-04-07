# Contrats & réservations

Cette statistique est exposée dans l'app Valrance sous l'entrée `Contrats & Réservations`.

## Finalité

Elle fournit une lecture consolidée des réservations sur une période, avec des indicateurs d'activité et de chiffre d'affaires répartis par grandes familles comptables.

## Filtres disponibles

La vue de recherche montre principalement :

- le centre ;
- l'organisation ;
- la date de début ;
- la date de fin.

Le provider prend aussi en charge d'autres filtres présents dans le code :

- statut de réservation ;
- type de réservation ;
- catégorie tarifaire ;
- type de client ;
- zone géographique française ;
- pays du client.

## Périmètre temporel

Le calcul se fait sur les réservations dont au moins un séjour se termine dans la période sélectionnée. Le code s'appuie en pratique sur `date_to`.

Les réservations annulées sont exclues.

## Indicateurs produits

La statistique retourne notamment :

- le centre et son type ;
- le numéro de réservation ;
- un statut simplifié ;
- le type de réservation ;
- le nombre de personnes ;
- le nombre de gratuités ;
- le nombre de nuitées ;
- le nombre d'unités locatives ;
- le nombre de nuitées-personnes ;
- le nombre d'activités ;
- le client, sa zone et son pays ;
- plusieurs sous-totaux de chiffre d'affaires.

## Catégories de chiffre d'affaires

Le provider reconstitue les montants à partir des règles comptables des lignes de réservation. Les grands regroupements sont :

- adhésions ;
- nuitées et repas ;
- animations internes ;
- prestataires et transports ;
- blanchisserie ;
- total.

Cette approche rend la statistique cohérente avec la comptabilité, plutôt qu'avec une simple classification produit.

## Simplification des statuts

Pour la restitution, certains statuts sont regroupés :

- plusieurs statuts intermédiaires sont ramenés à `confirmed` ;
- les statuts de facturation et de clôture sont ramenés à `invoiced`.

L'objectif est d'obtenir une lecture plus exploitable dans un tableau de pilotage.
