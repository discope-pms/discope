# Intégrations & exports

Cette page documente les traitements techniques spécifiques à Lathus qui échangent avec des systèmes externes ou produisent des fichiers destinés à d'autres canaux.

## Récupération des inscriptions CPA Lathus

Le provider `data/camp/enrollments.php` interroge une API externe pour récupérer un lot d'inscriptions.

### Configuration attendue

- `sale.integration.camp.enrollments.api_uri`
- `sale.integration.camp.enrollments.api_key`

### Comportement

- appel HTTP GET ;
- passage de la clé dans le header `X-API-KEY` ;
- support des paramètres `page` et `limit` ;
- reconstitution de l'URL à partir de l'URI configurée, y compris si elle contient déjà une query string.

### Point d'attention

Le code note explicitement qu'au 2026-03-12, le paramètre `limit` n'est pas réellement pris en charge côté API distante.

## Export CSV des camps pour le site

Le provider `data/camp/export-camps-csv.php` génère `site_camps.csv`.

### Périmètre exporté

- camps de la saison calculée ;
- uniquement les camps non CLSH ;
- uniquement les camps au statut `published`.

### Règles de calcul

- la saison visée bascule sur l'année suivante à partir de septembre ;
- la lettre tarifaire `A`, `B` ou `C` est déduite du nom du produit ;
- le niveau équestre est inféré depuis le `short_name` via `galop 1`, `galop 2` ou `galop 3` ;
- le nombre de places restantes est calculé comme `max_children - enrollments_qty`.

### Point de vigilance

Si le nom du produit ne contient pas de lettre tarifaire identifiable, le provider lève une erreur de configuration.

## Export CSV des tarifs pour le site

Le provider `data/camp/export-tariffs-csv.php` génère `site_tarifs.csv`.

### Règles de sélection

- recherche d'une liste de prix contenant `camp` dans son nom ;
- filtre sur l'année de saison ;
- prise en compte des produits de camp de type `full`.

### Structuration des tarifs

Les tarifs sont regroupés par lettre `A`, `B`, `C`, puis ordonnés par prix.

Les classes camp sont traduites vers les libellés métier :

- `close-member`
- `member`
- `other`

### Particularité importante

Le fichier ajoute ensuite en dur une série de tarifs historiques ou spécifiques (`D`, `L`, `P`, `T`, `W`).

Pour les DEV, cela signifie que l'export n'est pas purement dérivé du référentiel courant : une partie du contenu reste codée en dur.
