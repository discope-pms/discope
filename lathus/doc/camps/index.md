# Camps — Vue d’ensemble

L'application Camps permet la gestion des camps d'été du CPA Lathus. Chaque camp a un thème et un tarif, des parents ou tuteurs peuvent y inscrire leurs enfants âgés de 6 à 16 ans.

Les inscriptions peuvent être réalisées :
- par les parents sur le site www.cpa-lathus.asso.fr (pour les camps classiques, pas les CLSH)
- par les employés du CPA Lathus dans Discope (contact téléphone/mail avec un parent)

Types de camps :

- Classique
  - L'enfant est hébergé du dimanche soir au vendredi fin d'après-midi
  - L'enfant participe à des activités du lundi au vendredi

- Centre de vacances et de loisirs (CLSH)
  - L'enfant n'est pas hébergé
  - L'enfant est inscrit par jour
  - Peut durer 4 à 5 jours, jamais durant le week-end

Notes :
- Le nombre de places maximum dans un camp est égale à Qté groupe × Max enfants.
- Les inscriptions de status Brouillon, Confirmée et Validée sont prises en compte.
- Le site d'un camp est déterminé en fonction de sa tranche d'âge (Criquets 6-9, Coccinelles 10-12, Libellules 13-16).

## Workflow des camps

### 1. Pré-inscription

- Déclenchement : lorsqu'un parent effectue une inscription via le site web.
- Statut logiciel : `en cours` (non validée, en attente de traitement).
- Traitement :
  - si des places sont disponibles, un document `demande-camp` est envoyé pour chaque enfant concerné ;
  - s'il n'y a plus de place, l'inscription passe en liste d'attente avec un statut distinct.
- Important : aucune inscription sur la liste d'attente n'est possible via le site web. Les listes d'attente sont gérées uniquement par téléphone.
- Aucun paiement n'est requis à ce stade pour les listes d'attente.

### 2. Inscription confirmée

- Déclenchement : après réception du paiement et des documents nécessaires.
- Statut logiciel : `confirmé`.
- Traitement :
  - envoi d'un email de confirmation d'inscription ;
  - envoi d'un document `confirmation-camp` pour chaque enfant inscrit.

### 3. Publication du stage

La visibilité publique d'un camp est pilotée par deux statuts :

- `brouillon` : non publié, pas visible en front-end ;
- `publié` : visible par les familles.

### 4. Annulation / transfert

- Une inscription peut être annulée via le statut `annulé`.
- Le workflow prévoit également le transfert d'une inscription vers un autre camp.
- Cette action requiert une validation manuelle par un opérateur.

## Ce que le package ajoute

Le code du package complète ce périmètre avec plusieurs éléments spécifiques :

- un connecteur de récupération d'inscriptions depuis l'API CPA Lathus ;
- des champs complémentaires sur les tuteurs, institutions et inscriptions ;
- des exports CSV pour le site web ;
- un certificat annuel de camp par enfant ;
- une app de statistiques dédiée à la fois aux réservations et aux camps.

## Public cible de cette documentation

- PO : comprendre les règles métier, les documents disponibles et les flux d'exploitation ;
- DEV : localiser les providers, vues et surcharges qui matérialisent ces règles.
