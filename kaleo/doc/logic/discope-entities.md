## Unités locatives

Le cœur de métier de Discope est l'organisation de séjour, c'est à
dire la mise à disposition (location) d'infrastructures et de logements
(bâtiments, chambres, lits). Les infrastructures qui sont sujettes à la
location sont regroupées sous l'appellation **unités locatives**.

Une unité locative est :

-   Composée d'un libellé et d'une description

-   Est éventuellement rattachée à une unité locative parente (un lit
    qui peut se louer individuellement, qui se trouve dans une chambre
    qui peut se louer de manière "privative")

Des catégories d'unités locatives sont définies et permettent de
regrouper les unités en fonction de leur capacité ou des usages auxquels
elles sont destinées, de manière qu'il soit possible, pour une capacité
donnée ou un type de séjour donné, de trouver la liste des unités
locatives qui peuvent y correspondre.

#### Assignation des logements

Pour l'assignation des logements, il faut pouvoir choisir les logements
manuellement. Dans tous les cas il faut des UL d'une capacité totale de
nb_pers

Regarder le produit sélectionné, qty_accounting_method, capacity (s'il
est 'au logement', la capacité doit être définie ; s'il est 'à la personne'
la capacité est toujours 1)

-   Si qty_accounting_method = 'accomodation' alors nb_UL = 1

-   Si qty_accounting_method = 'person' alors nb_UL = de 1 (on met
    toutes les personnes dans la même UL) à nb_pers (chaque personne est
    dans une UL distincte)

Il faut pouvoir assigner un nombre de personnes à chaque UL 
(validation: nb toujours inférieur à la capacité de l'UL)

Note : ce qu'on ne sait pas faire : assigner des logements différents
d'un jour à l'autre pour un même séjour

#### Vérification des surbookings (pour une réservation donnée)

-   Prendre toutes les consommations liées à la réservation avec
    is_rental_unit = 1

    -   Prendre date et schedule_from et schedule_to et rental_unit_id

-   Grouper les consommations par rental_unit (triées par date)
-   Pour chaque groupe

    -   Déterminer la date d'arrive ; et la date de dernière consommation
        => date_to
    -   Rechercher dans les consommations les autres consommations (id not
        in group_ids) pour la même rental_unit dont la date est >=
        date_from ET <= date_to
    -   Filtrer les résultats (collisions):

        - Si conso.date == date_from (premier jour) OU conso.date == date_to (dernier jour)
        - Si schedule_to < group[0].schedule_from => retirer la conso des résultats
        - Si schedule_from > group[0].schedule_to => retirer la conso des résultats

    -   Si les résultats n'est pas vide : surbooking

## Découpe logique

#### Ressources

Des ressources globales sont définies :

-   Un catalogue de produits (organisé en familles et groupes) (il peut
    y en avoir plusieurs, mais un seul en cours de validité)
-   Un catalogue de produits "Packs" forfaits (produits is_pack)
-   Une liste de catégories tarifaires
-   Une liste de type de clients (natures)
-   Une table des saisons (qui définit la nomenclature des saisons
    possibles)
-   Un catalogue de règles comptables (organisé en catégories)
-   Des listes de prix (organisées en catégories)
-   Des listes de réduction (organisées en catégories)
-   Des listes de saisons (organisées en catégories)
-   Des listes de centres (organisées en catégories)
-   Des listes de templates (organisées en catégories)

#### Centre

À un centre sont associés :

-   Une famille de produits (qui permet de reconstituer le catalogue
    complet en remontant de famille en famille jusqu'à la racine)
-   Une catégorie de liste de prix
-   Une catégorie de liste de réductions
-   Une catégorie de liste de saisons
-   Une catégorie de centres
-   Une catégorie de templates
-   Une organisation parente (qui définit les informations bancaires et
    de facturation)

#### Catalogue

Il est possible de découper le catalogue de produits en une hiérarchie
des familles. Chaque nœud est une famille, accessible à un ou plusieurs
centres. Le catalogue d'un centre donné correspond à la liste des
produits (feuilles) pour tous les nœuds auxquels il a accès (chaque
centre a potentiellement accès à plusieurs familles de produits).

Une feuille de l'arborescence correspond à un produit (SKU), et chaque
produit est présent une et une seule fois dans l'arborescence.

Les taux de TVA applicables sont définis au niveau des listes de prix
(et donc indépendamment du catalogue). Les familles permettent donc de
consulter les produits par catégories de centre, et les groupe par
centre individuel.

Lorsqu'un produit ne doit plus être vendu, il reste dans le catalogue,
mais son champ « Peut être vendu » est mis à « Non ».
