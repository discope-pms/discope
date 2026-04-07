## Produits

Les produits (ou "articles") servent à :

-   Lister les services qui sont vendus, sous la forme d'un catalogue
-   Définir les prix de ces services (sur base de listes de prix)
-   Définir le taux de TVA applicable (en y assignant des règles
    comptables)
-   Préciser la manière dont doit être comptabilisée la vente d'un
    produit au niveau comptable
-   Préciser l'impact de la vente d'un produit sur l'organisation
    (lits disponibles, repas à cuisiner, chambres à nettoyer, ...)

Les produits n'ont pas de date limite de validité, mais peuvent être
marqués comme pouvant être vendu ou non.

### Types

Les **types** possibles des produits sont hiérarchisés de la manière
suivante :

-   **Consommables** : des produits physiques qui font partie d'un
    stock. Leur vente implique la diminution du stock disponible.
    -   Simple : il n'y a pas de suivi formel des consommables simples
        et la gestion du stock n'est pas supervisée.
    -   Stockable : les consommables stockables impliquent la gestion
        détaillée d'un stock avec la possibilité d'assigner des règles
        de commande automatique.

-   **Services** : des produits de type services ne se stockent pas et
    leur disponibilité est, a priori, illimitée (dans les faits, il y a
    toujours des conditions qui limitent la disponibilité)
    -   Simple : les services simples n'impliquent aucun suivi au
        niveau d'autres flux.
    -   Planifiable : les services planifiables impliquent la
        réservation d'une ou plusieurs autres ressources, en précisant
        éventuellement une *date*, une *heure* ou une *plage horaire*.

Exemple de **services** : repas (déjeuner, lunch, diner, pic-nic,
buffet, goûter) animation, activité, location d'une salle, location de
matériel, location de bus avec chauffeur, ...

Exemple de **consommables** : snacks, boissons, plat à emporter, petite
restauration...

### Mode de comptabilisation

Les produits peuvent être comptabilisés **à l'unité**, **au logement**,
ou **à la personne**.

-   Par défaut, un produit est comptabilisé à **l'unité**.

-   Un produit comptabilisé au **logement** est facturé au prorata
    nombre de jours (ou de nuits), quel que soit le nombre de
    participants et est assigné à une unité locative, ou à une catégorie
    d'unités locatives (auxquelles correspond un attribut
    '**capacité**', utilisé pour gérer les compositions).

-   Un produit comptabilisé à la **personne** est facturé en fonction du
    nombre de participants et dispose d'un attribut '**durée**' (en
    jours ou nuits) qui permet de générer le planning des services
    associés.

Note : Les nuitées représentent un cas particulier de services
"planifiables" comptabilisés au sein du logement, impliquant
l'occupation d'une unité locative dont le stock est limité.

Les consommations se réfèrent exclusivement à des services planifiables.

<div style="margin-top: 20px;">1. <b>Si</b> le produit est logement (is_accomodation = true)</div>

> Il peut s'agir :

> -   D'un logement individuel (nuitée) : qty_accounting_method = 'person'
> -   Ou d'un logement de groupe (ex. un gîte): qty_accounting_method = 'accomodation'

<div style="margin-left: 15px; margin-bottom: 15px;">
<b>Si</b> la comptabilisation se fait à la personne (qty_accounting_method = 'person'), <br>
<div style="margin-left: 15px;">alors la quantité correspond à : nb_personnes x nb_jours</div>
</div>

<div style="margin-left: 15px; margin-bottom: 15px;">
<b>Si</b> la comptabilisation se fait au logement (qty_accounting_method = 'accomodation'),  <br>
<div style="margin-left: 15px;">alors la quantité correspond à : nb_jours</div>
</div>

<div style="margin-left: 15px; margin-bottom: 15px;">
<b>Si</b> la comptabilisation se fait à l'unité (qty_accounting_method ='unit'),  <br>
<div style="margin-left: 15px;">alors la quantité est indépendante (défaut = 1)</div>
</div>

<div style="margin-top: 20px; margin-bottom: 15px;">2. <b>Si</b> le produit n'est pas un logement</div>

<div style="margin-left: 15px; margin-bottom: 15px;">
<b>Si</b> la comptabilisation se fait à la personne (qty_accounting_method = 'person'),
<div style="margin-left: 15px;">alors la quantité correspond à : nb_personnes x nb_jours</div>
</div>

<div style="margin-left: 15px; margin-bottom: 15px;">
<b>Si</b> la comptabilisation se fait à l'unité (qty_accounting_method = 'unit'),
<div style="margin-left: 15px;">alors la quantité est indépendante (défaut = 1)</div>
</div>

(**Si** 'is_meal' = true, le décalage éventuel est utilisé pour les consommations)

<br>

### Organisation

Les produits sont organisés par **famille**, par **groupes**, et par
**catégories**.

-   Les familles sont définies selon une structure hiérarchique qui
    constitue le catalogue de l'Organisation.

-   Les familles sont utilisées pour organiser les produits sur base des
    différents centres.

-   Les groupes permettent, au sein d'une même famille, de regrouper
    des produits (par exemple pour définir les produits qui peuvent être
    vendus au bar)

-   Les catégories permettent de regrouper des produits indépendamment
    de leur famille, et sont utilisées pour la gestion de l'application
    des produits systématiques.

Un produit appartient toujours à une seule famille, à un ou plusieurs
groupes, et à une ou plusieurs catégories.

### Produits, Modèles et Variantes

Dans certains cas, un produit est disponible selon différente variantes.

Les options de produits permettent de définir les options disponibles
par famille de produits. A chaque variante de produit est associée une
liste d'options avec les valeurs correspondantes.

Les attributs communs sont définis au niveau du modèle du produit.

Cette logique est utilisée pour proposer différents prix en fonction des
**tranches d'âge**.

Ainsi, dans la famille de produit principale de l'organisation, une
option "tranche d'âge" est disponible avec la liste de valeurs
possibles suivantes :

-   Bébé (0-3)
-   Maternelle (3-6)
-   Primaire (6-12)
-   Secondaire (12-26)
-   Adulte (26-99)

Les variants peuvent également être utilisées pour décliner les produits
sur d'autres options.

Chaque variant (produit qui peut être vendu) dispose d'un identifiant de
type SKU (Stock Keeping Unit).

Même si d'un point de vue logique, la plupart des produits de type
"séjours" du catalogue sont présentés de manière identique aux
clients, il y a une distinction entre les produits selon
l'assujettissement à la TVA de l'entité légale à laquelle sont
rattachés les centres qui les proposent.

Les variants disposent donc d'un SKU et d'un nom, et il est possible
d'avoir des produits avec des noms et descriptions identiques mais des
SKU distincts.

#### Ordre de préférence par équipe de gestion

Pour chaque équipe de gestion, il est possible de définir une liste de
produits de préférence (`ProductFavorite`).

Une préférence est un lien vers un produit avec un ordre spécifique.

Au sein de l'écran "services réservés", les préférences sont
utilisées pour afficher les premiers produits, les suivants sont dans
l'ordre défini par l'entité (on s'assure qu'il n'y ait pas de
doublons) Dans tous les cas, la liste est limitée à 20 éléments.

## Packs

Dans certains cas, il est possible de vendre des **forfaits** (ou
"packages") qui incluent plusieurs produits (ex. : "Séjour classe
découverte", "Stage nature en internat", "B&B", ...) vendus à un
prix forfaitaire.

(Au niveau conceptuel : s'il est possible d'attribuer un prix à un
objet, c'est que cet objet est un Produit. Les **Packs** **forfaits**
sont donc bien des produits.)

Les Packs sont en quelque sorte des super-produits : ils ont les mêmes
caractéristiques que les produits (il est possible d'y assigner un
prix, des règles comptables, et un mode de comptabilisation) et ils
peuvent être ajoutés à une réservation.

Les produits d'un pack sont comptabilisés selon le mode de
comptabilisation du pack: soit au logement; soit à la personne.

Dans la plupart des cas, lors d'une réservation, ce sont les forfaits
**séjours** qui sont utilisés.

Le principe des séjours est de proposer des formules de location à un
prix forfaitaire (généralement comptabilisé sur base du nombre de
personnes).

La quantité de chaque produit est ajustée automatiquement en fonction
des détails du pack (nombre de personnes, catégorie tarifaire, type de
séjour) et de la configuration du modèle de produit correspondant
(quantité propre, comptabilisation à l'unité, à la personne ou au
logement).

<div style="margin-left: 15px">
Exemples :

<div style="margin-top: 10px; margin-bottom: 20px;">
<b>CDV-3J-PC-MAT</b><br>
=> on bloque le nombre de nuitées (2 nuits / 3 jours) et on fait varier
les personnes (le montant inclut 2x3 repas + 2 nuits).
<br>
Il s'agit d'un produit comptabilisé **à la personne** et disposant
d'un attribut '**durée**' (dans ce cas-ci, 'durée'= 2) qui permet
d'ajuster le planning.
</div>

<div style="margin-bottom: 40px;">
<b>CH-3P-PC</b><br>
=> on bloque le nombre de personnes et on fait varier le nombre de
nuitées (pour chaque nuitée sont comptabilisés 1 chambre 3 personnes +
3x3 repas).
<br>
Il s'agit d'un produit comptabilisé **au logement** et rattaché à une
unité locative (ou à une catégorie d'unités locatives) disposant d'un
attribut '**capacité**' (dans ce cas-ci, 'capacité'= 3) qui permet de
retrouver les unités locatives auxquelles correspondent ce service.
</div>
</div>

Il est possible de gérer les exceptions : les produits peuvent être
marqués comme disposant de leur propre quantité (par exemple pour n'être
comptabilisé qu'une seule fois ; ex. frais de séjour `own_qty` = 1).

Dans le cas d'un séjour, un forfait est constitué :

-   D'un logement (nuitée)
-   D'une pension (repas)
-   D'éventuels compléments (animation, ...)
-   Des frais fixes

Lorsque les produits sont réservés par séjour, il est possible
d'ajuster les consommations de manière indépendante (les moments
auxquels les personnes seront effectivement présentes pour les repas,
pour les chambres), mais le prix comptabilisé est celui du pack
(forfait), même dans le cas où certains produits présents dans le pack
ne sont finalement pas "consommés".

Lors de la création d'un pack, le prix par défaut du pack est calculé
sur base des prix de chacun de ses produits, mais peut être modifié
manuellement.

Les produits, y compris les packs, sont tous identifiés par un code SKU
(stock keeping unit), unique et invariable.

Pour cette raison, lorsque la composition d'un Pack doit être modifiée,
le Pack original doit être dupliqué et la copie peut alors être
modifiée. L'ancien Pack est alors marqué comme inactif \["Peut être
vendu"\] (pour qu'il n'apparaisse plus dans la liste des produits à
proposer).

Cas de figure : prises de réservation anticipatives

Il est envisageable que la composition des Packs pour l'année suivante
soit distincte de celle de l'année en cours (avec des grilles
tarifaires distinctes). Pour permettre cette situation, les Packs
utilisent toujours un SKU avec un préfixe similaire, et une variation du
suffixe du SKU et de la description (par exemple renseignant l'année
d'application du Pack).\
A chacun des Packs (identifiés par un SKU distinct), il est possible
d'assigner un prix via les listes de prix qui s'appliquent à chacune
des périodes définies, de la même manière que les autres produits.

Lorsqu'un Pack n'est plus d'application, il est marqué comme inactif
("Peut être vendu" est mis à « non »).

Au niveau des **variantes** en fonction des tranches d'âges, un produit
Séjour CDV peut ainsi être décliné en :

-   Séjour CDV (*modèle*); CDV-MAT (*SKU*); CDV maternelle
    (*description*); \["tranche d'âge" = "Maternelle (3-6)"\]

-   Séjour CDV (*modèle*); CDV-PRI (*SKU*); CDV primaire
    (*description*); \["tranche d'âge" = " Primaire (6-12)"\]

-   Séjour CDV (modèle); CDV-SEC (*SKU*); CDV secondaire
    (*description*); \["tranche d'âge" = "Secondaire (12-26)"\]

Ces variantes permettent à la fois d'assigner des tarifs distincts et
d'attribuer l'imputation de ces produits avec des règles statistiques
distinctes. (en utilisant des règles comptables communes au modèle).

Pour faciliter la recherche au sein du catalogue, il est recommandé
(mais pas obligatoire) de regrouper les produits en utilisant les
groupes de produits et les catégories.

La logique suivante a été retenue :

-   Dans la plupart des cas, les packs sont des templates qui comportent
    plusieurs services. La quantité de chacun de ces services est
    déterminée en fonction du nombre de nuitées et de personnes définis
    dans le groupe.

-   Dans le catalogue, le prix n'est pas défini au niveau du pack, mais
    bien au niveau de chacun des services.

-   Les packs templates ne sont pas fixes, c'est à dire qu'il est
    possible d'y ajouter d'autres services (pour autant qu'ils soient
    compatibles avec le groupe en cours : tranche d'âge, période, type
    de gite, catégorie tarifaire)

Par contre, dans les documents, lorsqu'un groupe est lié à un pack, on
renseigne le prix total pour le groupe. Le détail des services est
repris avec les quantités, mais pas les tarifs.

Pour qu'un pack apparaisse dans la liste, même s'il s'agit d'un
template, il faut qu'une ligne correspondante soit présente dans la
liste de prix ciblée par la réservation (ceci permet de définir pour
quelles plages de date un pack est disponible ou non).