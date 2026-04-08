# Documents & Emails

## Documents

- Définition des documents requis par modèle ou camp.
- Génération automatique des documents d’inscription.
- Suivi de complétude (“tous reçus”).

## Certificat de camp

Le package contient un document spécifique `print-camp-certificate` généré à partir d'un enfant et d'une année.

### Finalité

Ce certificat consolide les camps suivis par un enfant sur une année et permet de produire un justificatif à destination des familles ou institutions.

### Données prises en compte

Le provider agrège notamment :

- l'enfant ;
- le tuteur principal ;
- l'institution éventuelle ;
- le centre et l'organisation ;
- les inscriptions de l'année ;
- le nombre total de jours ;
- le montant total payé.

### Particularités métier

- pour les camps CLSH, le nombre de jours est recalculé à partir des présences journalières ;
- pour les camps non CLSH, le week-end supplémentaire influe sur le nombre de jours ;
- le rendu final s'appuie sur un template de type `camp` et code `certificate`.

## Emails

- Suivi des mails liés aux inscriptions : Pré-inscription, Confirmation.
- Modèle dédié pour tracer l’historique d’envoi.

## Notes DEV

Le certificat de camp s'appuie sur :

- `data/sale/camp/print-camp-certificate.php`
- `views/sale/camp/Child.print.camp-certificate.html`

Le document injecte des `TemplatePart` provenant de la catégorie de template du centre.
