# Tarification & Produits

## Catalogue

- Produits et modèles de produits spécifiques aux camps.
- Produits standards : séjour complet, week-end, samedi matin, CLSH 4/5 jours.

## Tarification

- Classes de camp : `other`, `member`, `close-member`.
- CLSH : tarification par quotient familial.

## Lignes d’inscription (EnrollmentLine)

- Calcul des montants HT / TTC, TVA, quantités.
- Recalculs cohérents en cascade.

### Produits détaillés

Les produits de camps ne peuvent être utilisés que pour les inscriptions aux camps.

Il existe 4 types de produits de camps :

- Classique
  - Camp complet (Tarif séjour A/B/C)
  - Samedi matin (fin séjour samedi matin)
  - Week-end (lier 2 séjours)

- CLSH
  - Camp à la journée (Tarif CLSH journée)

### Prix — Camp complet

Champ `Classe de camp` pour prix spécifique selon la classe (3 prix nécessaires).

### Prix — Camp à la journée

Champ `Classe de camp` (2 classes utilisées) et `Quotient familial min/max` (tranches QF). 8 prix nécessaires.

