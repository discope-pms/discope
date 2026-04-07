# Impressions et documents

Le package Valrance personnalise trois documents d'impression :

- la réservation ;
- le contrat ;
- la facture.

Dans chaque cas, le principe est identique :

- un provider PHP prépare les données ;
- un template HTML dédié produit le rendu final ;
- la sortie peut être générée en HTML ou en PDF.

## Particularités communes

Les documents personnalisés Valrance s'appuient notamment sur :

- les coordonnées du centre ou du bureau selon `use_office_details` ;
- des blocs de contenu injectés via `TemplatePart` ;
- une présentation des consommations pilotée par `booking_schedule_layout` quand elle est disponible ;
- des informations de contact et de facturation enrichies.

## Documents disponibles

- [Réservation](booking-print.md)
- [Contrat](contract-print.md)
- [Facture](invoice-print.md)
