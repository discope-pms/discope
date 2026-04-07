# Impression de la facture

La facture Valrance dispose elle aussi d'un provider dédié.

## Objectif

Ce provider adapte la facture imprimée aux attentes du Relais Valrance tout en réutilisant les objets de facturation standard de Discope.

## Données mobilisées

Le document assemble notamment :

- la facture et ses lignes ;
- l'organisation émettrice ;
- le centre et éventuellement le bureau ;
- la réservation liée ;
- les informations client ;
- les notices documentaires utiles à la facturation.

## Détails bureau / centre

Comme pour les autres impressions Valrance, le rendu peut basculer vers les coordonnées du bureau si le centre le demande via `use_office_details`.

## Proforma et notices

Le provider récupère au moins le bloc `proforma_notice`, ce qui permet d'insérer une mention documentaire dédiée lorsque le document le nécessite.

## Paiement

Le code prévoit également les éléments nécessaires à une logique de QR de paiement, ce qui indique que l'impression de facture peut embarquer des informations de règlement enrichies.
