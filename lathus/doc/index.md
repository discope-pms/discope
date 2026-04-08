# Doc Lathus

Bienvenue dans la documentation Lathus.

Cette documentation couvre :

- les fonctionnalités métier visibles côté utilisateurs ;
- les personnalisations propres au package `lathus` ;
- les points techniques utiles aux DEV pour comprendre les surcharges, documents et intégrations.

## Périmètre du package

Le package Lathus complète Discope sur trois axes principaux :

- la gestion des camps et inscriptions ;
- les vues et documents liés aux réservations et aux séjours ;
- des exports et statistiques spécifiques, notamment pour le site web et l'exploitation interne.

## Ce que le code ajoute réellement

À partir des classes, providers et vues du package, on retrouve notamment :

- une app `Statistiques` dédiée ;
- des impressions personnalisées pour réservation, contrat, facture, planning d'activités et répartition des chambres ;
- un certificat de camp pour les enfants ;
- des exports CSV pour le site Lathus ;
- un provider d'intégration pour récupérer les inscriptions depuis l'API CPA Lathus ;
- des surcharges de modèle sur les inscriptions, tuteurs et institutions.

## Parcours conseillé

- `Planning` pour les vues opérationnelles de semaine et d'activités ;
- `Camps` pour le métier principal d'inscription et de suivi ;
- `Réservations` pour les documents spécifiques ;
- `Technique` pour les intégrations, exports et extensions du modèle.
