# Documents de réservation

Le package Lathus personnalise plusieurs documents liés aux réservations.

## Périmètre

Les providers présents dans le code couvrent :

- l'impression du devis ou de la réservation ;
- l'impression du contrat ;
- l'impression de la facture ;
- le programme d'activités d'une réservation ;
- la répartition dans les chambres.

## Devis / réservation

Le provider `data/sale/booking/print-booking.php` prépare un document en mode `grouped` ou `detailed`.

### Fonctionnalités visibles

- regroupement des lignes par `grouping_code` quand le produit ou son modèle en définit un ;
- remplacement dynamique de `{nb_pers}` dans certains libellés de regroupement ;
- calcul des quantités adultes / enfants à partir des tranches d'âge ;
- injection de `TemplatePart` du template `quote` rattaché à la catégorie du centre.

### Intérêt métier

Cela permet d'obtenir une présentation commerciale plus lisible qu'une simple liste de lignes techniques.

## Contrat

Le provider `data/sale/booking/print-contract.php` enrichit le contrat avec plusieurs informations métier.

### Informations calculées

- contact de séjour ;
- nombre total d'adultes et d'enfants ;
- hébergement par grande catégorie ;
- premier et dernier repas ;
- présence ou non d'activités ;
- échéances d'acomptes via les financements ;
- chèque de caution de 305 EUR si le client n'est pas marqué comme `trusted`.

### Détails techniques utiles

- les produits sont regroupés via `grouping_code` ;
- les acomptes sont déduits des `fundings_ids` de type `installment` ;
- le montant de la rubrique annulation est calculé sur base des acomptes attendus ;
- la présence d'activités peut venir soit de vraies lignes activité, soit de produits dont le SKU contient `-MAP`.

## Facture

Le provider `data/sale/booking/print-invoice.php` gère la présentation de la facture et du solde à payer.

### Fonctionnalités visibles

- regroupement des lignes comme pour le devis et le contrat ;
- affichage du détail des financements encore ouverts ;
- restitution de l'historique de paiements ;
- calcul du reste à payer ;
- génération d'un QR code de paiement EPC / SEPA quand les données sont suffisantes.

### Notes techniques

Le QR code s'appuie sur :

- l'IBAN et le BIC de l'organisation ;
- la communication structurée ou référence de paiement ;
- le solde restant calculé.

Le provider filtre aussi certains financements pour éviter d'afficher des acomptes déjà refacturés ou des cas non pertinents selon le type de facture.

## Programme d'activités

Le document `print-booking-activity` produit un planning imprimable des activités d'une réservation.

### Ce qu'il assemble

- les groupes de séjour ;
- les activités triées par date et tranche horaire ;
- les repas associés ;
- les informations centre, client et organisation ;
- un template documentaire de type `activity.doc`.

### Particularité

Le document peut être limité à un seul `booking_line_group_id`, ce qui permet une impression ciblée par groupe de séjour.

## Répartition dans les chambres

Le document `print-room-plans` produit une répartition des chambres par bâtiment.

### Fonctionnement

- les bâtiments sont identifiés par leurs plans attachés aux unités locatives racines ;
- les unités locatives sont regroupées par bâtiment principal ;
- si une unité a des enfants, le provider déroule récursivement les sous-unités terminales ;
- la sortie mélange plan du bâtiment et tableau des chambres, lits et lits supplémentaires.

### Usage métier

Ce document sert à la préparation d'arrivée et à la logistique d'hébergement.
