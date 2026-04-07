# Impression du contrat

Le contrat Valrance utilise un provider et un template HTML spécifiques, distincts du standard.

## Données prises en charge

Le document agrège :

- les données de la réservation ;
- les informations du client et du payeur ;
- les coordonnées du centre ou du bureau ;
- les groupes de services, activités et repas ;
- les informations utiles à la contractualisation.

## Présentation des consommations

Comme pour l'impression de réservation, le mode d'affichage dépend de `booking_schedule_layout` quand cette information est disponible sur le type de réservation.

Cette logique permet d'aligner le contrat sur le mode de restitution attendu pour Valrance.

## Clauses et blocs dynamiques

Le code fait appel à plusieurs `TemplatePart`, notamment :

- `advantage_notice`
- `contract_agreement`

Le contrat n'est donc pas seulement un rendu de lignes ; il incorpore aussi des contenus documentaires configurables.

## Éléments de paiement

Le provider prépare également les éléments utiles à la restitution des coordonnées bancaires et de certains dispositifs associés, y compris les éléments nécessaires à une logique de QR de paiement.
