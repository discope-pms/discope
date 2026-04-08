# Inscriptions

## Rôle

Lien entre un enfant et un camp.

## Contenu

- Enfant inscrit.
- Camp et groupe.
- Classe tarifaire.
- Produits et lignes d’inscription.
- Options spécifiques : Week-end, CLSH par jour, Garderies matin / soir.

## Workflow

- pending, waitlisted, confirmed, validated, cancelled

## Origine des inscriptions

Le package gère deux origines principales :

- les inscriptions encodées dans Discope par les équipes ;
- les inscriptions récupérées depuis l'écosystème CPA Lathus via un provider d'intégration.

Le provider `data/camp/enrollments.php` appelle une API externe configurée dans les settings et relaie les données brutes JSON.

## Politiques

- Capacité du camp/groupe, quota ASE, présence et complétude des documents requis.

## Automatisations

- À la confirmation : Verrouillage, génération des financements, génération des présences.
- À l’annulation : Déverrouillage, suppression des présences, nettoyage des financements et paiements.

## Données spécifiques Lathus

La surcharge de classe `lathus\sale\camp\Enrollment` ajoute un champ `phone`.

Objectif :

- conserver le numéro spécifique fourni lors de l'inscription ;
- disposer d'un canal direct pour la personne qui gère l'inscription, indépendamment d'autres coordonnées.

## Restrictions

- Nombre max d'inscriptions/camp (groupes supplémentaires pour ajouter des places).
- Quota ASE max par camp.
- Respect tranche d'âge (tolérance ±1 an).

## Envoi des emails

- Préinscription et Confirmation envoyées au tuteur principal depuis la fiche inscription (menu droit).

## Notes DEV

- Le provider d'intégration attend `sale.integration.camp.enrollments.api_uri`.
- Il attend aussi `sale.integration.camp.enrollments.api_key`.
- Le header HTTP utilisé est `X-API-KEY`.
- Le paramètre `limit` est prévu, mais le code note qu'il n'est pas pris en charge par l'API distante au 2026-03-12.
