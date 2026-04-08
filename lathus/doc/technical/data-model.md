# Modèle spécifique

Le package Lathus surcharge peu d'entités, mais les surcharges présentes sont significatives.

## Entités concernées

Les classes spécifiques sont :

- `lathus\sale\camp\Enrollment`
- `lathus\sale\camp\Guardian`
- `lathus\sale\camp\Institution`

Elles héritent des entités standard du module camp.

## Enrollment

Champ ajouté :

- `phone` : numéro spécifique donné par la personne qui gère l'inscription.

### Intérêt

Ce champ permet de conserver un contact opérationnel propre à l'inscription, potentiellement distinct des coordonnées habituelles du tuteur.

## Guardian

Le tuteur est enrichi avec :

- `mobile`
- `phone`
- `work_phone`
- `address_street`
- `address_city`

### Intérêt

Le package vise à stocker une information de contact plus complète, cohérente avec un contexte d'inscription camp et avec des données issues d'un système externe.

## Institution

L'institution est enrichie ou explicitée avec :

- `name`
- `email`
- `phone`
- `address_street`
- `address_zip`
- `address_city`

### Intérêt

Ces données permettent de produire des documents et certificats plus complets lorsqu'un enfant est suivi par une structure.

## Lecture DEV

Ces surcharges restent volontairement limitées :

- elles n'introduisent pas de nouveau workflow autonome ;
- elles renforcent surtout la qualité et la granularité des données ;
- elles sont cohérentes avec les providers d'impression et d'intégration présents dans le package.
