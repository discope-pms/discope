# Participants (Enfants, Tuteurs, Institutions)

## Enfant (Child)

- Identité, âge.
- Licences (FFE / CPA).
- Compétences.
- Classe de camp calculée.
- Lien vers inscriptions et présences.

### Tuteur principal

- Doit fournir les documents nécessaires et payer le montant de l'inscription.
- Reçoit les emails de préinscription et de confirmation.
- Priorité de contact (adresse postale/email/téléphone) pour l'inscription et le suivi durant le camp.

## Tuteurs (Guardian)

- Mère, Père, Tuteur légal, Membre famille, Responsable foyer, Conseil département, Garde d'enfants, Autre.
- Fiche tuteur : informations de contact d'un tuteur d'un enfant.

### Surcharge Lathus

Le package `lathus` surcharge l'entité `Guardian` pour conserver des informations plus riches, utiles au contexte CPA Lathus :

- mobile ;
- téléphone fixe ;
- téléphone professionnel ;
- adresse ;
- ville.

Cette surcharge permet d'absorber des données plus détaillées issues de l'intégration ou de l'encodage opérationnel.

## Institutions (Institution)

- Fiche institution : informations de contact d'une institution en charge d'un enfant.

### Surcharge Lathus

Le package ajoute ou redéfinit explicitement les données suivantes :

- nom ;
- email ;
- téléphone ;
- adresse ;
- code postal ;
- ville.

## Lecture technique

Les classes spécifiques se trouvent dans :

- `classes/sale/camp/Guardian.class.php`
- `classes/sale/camp/Institution.class.php`
- `classes/sale/camp/Enrollment.class.php`

Leur rôle n'est pas de refondre le modèle camp standard, mais de l'enrichir avec des données réellement exploitées par l'organisation.
