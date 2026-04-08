# Paiements & Financements

## Gestion financière

- Statut de paiement : `due` / `paid`.
- Montant payé cumulé.
- Références structurées (VCS / RN / RF).

## Rôle métier

Le volet paiements et financements permet de suivre, pour chaque inscription :

- le montant attendu ;
- le montant déjà reçu ;
- les références à utiliser pour les paiements ;
- le passage entre inscription confirmée, validée, annulée ou remboursée.

Dans la pratique, ce bloc sert autant au suivi administratif qu'à la validation opérationnelle avant le début du camp.

## Automatisation

- Création, mise à jour et nettoyage des financements et paiements selon les transitions de workflow des inscriptions.

## Déclencheurs principaux

Le fonctionnement documenté historiquement est le suivant :

- à la confirmation, des financements sont générés pour l'inscription ;
- à l'annulation, les financements et paiements doivent être nettoyés ou ajustés selon la situation ;
- une inscription validée peut l'être même si tous les paiements ne sont pas encore reçus, selon le contexte métier.

## Lecture fonctionnelle

### À la confirmation

La confirmation d'une inscription fige une partie du dossier et matérialise l'engagement financier.

Effets attendus :

- verrouillage de l'inscription ;
- génération des financements ;
- préparation du suivi des paiements ;
- possibilité d'envoyer la pré-inscription ou les documents associés.

### À la validation

La validation correspond à une inscription administrativement recevable pour le camp.

Elle dépend généralement de :

- la complétude documentaire ;
- un niveau de paiement jugé suffisant ;
- l'absence de blocage métier.

### À l'annulation

Une inscription annulée peut nécessiter :

- suppression ou révision des financements non pertinents ;
- conservation de certaines traces de paiement ;
- gestion d'un solde positif ou négatif selon qu'il y ait des frais d'annulation ou un remboursement.

## Références de paiement

La documentation historique mentionne l'usage de références structurées de type :

- `VCS`
- `RN`
- `RF`

L'objectif est de faciliter la réconciliation et le suivi des paiements reçus.

## Lien avec les autres flux

Le volet paiements est lié à :

- les emails de pré-inscription et de confirmation ;
- les statuts de l'inscription ;
- les documents produits pour les familles ;
- les statistiques d'inscriptions, d'aides et de financements.

## Notes DEV

Même si le package `lathus` n'ajoute pas ici une classe financière dédiée, plusieurs documents du package réutilisent les financements standard :

- les contrats récupèrent les acomptes pour les afficher ;
- les factures listent les fundings restants et les paiements déjà reçus ;
- les références structurées sont formatées dans les providers d'impression.
