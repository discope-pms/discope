Le prix final d'une consommation est établi en fonction du client et les
informations qui y sont rattachées (catégorie tarifaire ; tranche d'âge)
et la période du séjour.

-   Une grille tarifaire publique par défaut en cours de validité doit
    toujours exister (c'est la seule grille dans laquelle tous les
    produits doivent être repris)
-   À chaque centre peut être appliqué une grille tarifaire différente
    (qui vient surcharger un ou plusieurs produits)
-   Les grilles tarifaires sont applicables en cascade (certaines
    opérations permettent de surcharger la grille tarifaire)
-   Lors d'une réservation, c'est le centre ciblé pour la période
    ciblée, qui va déterminer le prix du produit
-   Une grille tarifaire peut en recouvrir une autre si et seulement si
    la grille recouverte est totalement incluse dans l'autre

Pour appliquer la logique de tarification on utilise un système de
réductions automatiques, qui correspond à un avantage accordé au client
d'une réservation, sur base d'une série de critères, dont le principal
est la catégorie tarifaire.

Pour des questions de transparence, sur les contrats une mention permet
d'identifier le tarif qui a été appliqué : ce qui correspond aux
réductions automatiques appliquées.

#### Avantages de Catégorie tarifaire et gratuités automatiques

Les avantages accordés sur base de la catégorie tarifaire fonctionnent
avec des adaptateurs de prix qui sont créés automatiquement au niveau
d'un groupe de service (séjour).

Discope permet d'appliquer des « réductions » (adaptations de prix) au
niveau des groupes de services (qui impactent alors tous les produits du
groupe, en se cumulant avec les réductions définies au niveau de chaque
produit).

Les avantages et gratuités automatiques s'appliquent en fonction de
certains critères qui sont liés à plusieurs aspects de la réservation :

-   Durée du séjour
-   Fidélité du client
-   Saison
-   Catégorie tarifaire du client
-   Nombre de personnes

Ces avantages s'appliquent en fonction du « catalogue de réduction »,
sur base des catégories de produits.

-   Les ristournes sont cumulatives avec un plafond (pour tous les
    produits) défini par catégorie tarifaire
-   Les ristournes sont définies selon au taux (cumulatif) et une
    condition
-   Les tests des conditions sont complexes et doivent être faits
    manuellement ; et pour chaque type de réduction, il y a des
    paramètres différents

Le taux de réduction final est calculé pour chaque ligne, mais peut
aussi être défini manuellement.

Les gratuités sont des réductions particulières (de type 'freebie')
qui se comptent en unités gratuites (là où les réductions standard
s'appliquent selon un taux). Lorsque les conditions sont remplies, le
nombre de gratuités est ajouté.

Note : Les équipes de gestion disposent d'un paramètre qui permet de
choisir s'il faut appliquer automatiquement les gratuités ou si elles
sont gérées manuellement.

Discope permet également de définir des seuils minimaux et maximaux de
réductions possibles. Ces seuils sont définis sur la base du type de
client (catégorie tarifaire) dans les Classes de Réduction. Il y a deux
types de plafonds (seuil de réduction maximum) :

-   Un plafond général
-   Un plafond distinct SI au moins une gratuité est appliquée

Pour chaque réservation, il peut y avoir plusieurs réductions. Les
réductions peuvent être appliquées soit sur les regroupements
(réductions automatiques), soit directement sur les produits (réductions
manuelles), mais pas sur la réservation complète.

Par défaut, une réduction s'applique (ajout automatique) lors de
l'ajout d'un produit à une réservation, en fonction du client, de la
date et du centre. Les réductions peuvent être modifiées manuellement
(pour réinitialiser, il suffit de retirer le produit et de l'ajouter à
nouveau).

Les réductions sont définies sur base de règles :

-   Règle contient plusieurs lignes de tests (conditions) à appliquer
    sur un couple « réservation + produit »
-   Liste de règles est rattachée à une catégorie de produits

Les listes de réductions sont toutes rattachées à des classes de
réduction, qui les associent à une catégorie tarifaire spécifique, en
fixant d'éventuels seuils de réduction max et min.

Au niveau de la nomenclature, des noms de critères sont prédéfinis, dont
la valeur est retrouvée en fonction d'une réservation et d'un produit
(une méthode qui retourne une map de clés/valeurs).

Un contrôleur spécifique est assigné à la résolution de cette map. Les
valeurs reconnues sont les suivantes :

-   `duration` : La durée relative au séjour considéré

-   `count_booking_24` : Le nombre de réservations faites par le client
    au cours des 24 derniers mois

-   `nb_pers` : Le nombre total de personnes du séjour

-   `nb_children` : Le nombre de personnes du séjour

-   `nb_adults` : Le nombre d'adultes du séjour

-   `season` : Le type de saison (basse, moyenne, haute)

Pour chaque produit sujet à une réduction, la liste des réductions
applicables est parcourue et vérifiée. Si une réduction applicable est
validée (toutes les conditions sont remplies \[conjonctions\]), elle est
ajoutée à la liste des réductions pour le produit considéré.

Les opérateurs reconnus sont : '>', '>=', '<', '<=', '='

Note : Pour les réductions automatiques de type "montant fixe", la
valeur s'entend HTVA.

Pour les gratuités (freebies), il est possible d'assigner une valeur de
seuil (value_max) correspondant à une des opérandes.

Il est également possible de limiter les gratuités (freebies) à
certaines tranches d'âges spécifiques.

##### Cas particuliers et Exceptions

Par défaut, les avantages sont appliqués sur les produits d'un groupe
de services, sur base de la catégorie tarifaire.

Dans certains cas particuliers, il peut être nécessaire de ne pas
appliquer les avantages de manière automatique.

Il est possible, au niveau des packs, de marquer les produits d'un pack
comme ne devant pas être sujets à l'application automatique des
avantages (toggle "Prix adaptables"). Ceci permet de désactiver la
création automatique des adaptateurs de prix.

Cette fonctionnalité n'est disponible que pour les Packs (modèles de
produit ou produits) et peut s'appliquer à l'un ou l'autre niveau :

-   Lorsqu'une modification est faite au niveau du modèle de produit,
    toutes les variantes (produits) sont mises à jour

-   Lorsqu'une variante (produit) est modifiée, cela n'impacte pas les
    autres variantes du modèle

Ce paramètre de configuration s'applique sur tous les produits du
séjour associé à un pack (y compris sur les produits hors-pack, ajoutés
manuellement au sein du même séjour).

#### Détail du calcul des avantages

Il y a deux façons d'accorder des avantages :

1) via réduction du prix unitaire (manuellement ou via des règles de
réductions automatiques)

2) en accordant des gratuités

Pour chaque ligne de réservation (un produit ou un service), le système
compare :

-   le montant théorique que le client aurait payé au tarif catalogue
    pour toute la quantité prévue,

-   avec le montant réellement facturé, qui peut être réduit grâce à des
    remises, des adaptations de prix ou des unités offertes.

La différence entre ces deux montants représente l'avantage économique
obtenu, calculé TVA comprise.

S'il y a plusieurs lignes regroupées (ex. : un pack ou une offre
combinée), le système additionne tous les avantages ligne par ligne pour
obtenir un total d'avantage au niveau du groupe.

#### Gestion des prix TVAC et HTVA

Dans Discope, les prix peuvent être encodés TVAC ou HTVA.

Lorsqu'un prix est encodé TVAC, son prix HTVA est calculé sur base de
la règle TVA associée, de manière à ce que le prix_HTVA x (1+TVA) =
prix_TVAC.

Ceci implique de stocker le prix HTVA avec une précision de 4 décimales.

Les prix unitaires sont toujours communiqués au client avec une
précision de 2 décimales.

Dans le cas d'un prix HTVA avec une précision de plus de 2 décimales, on
affiche le prix arrondi, tout en conservant la précision (afin que 1 x
prix_unitaire x (1 + TVA) corresponde bien au prix catalogue TVAC. C'est
le seul pour lequel qty x prix_unitaire_affiché peut ne pas correspondre
au prix_total_affiché.

Par ailleurs, le prix unitaire est susceptible d'être adapté afin
d'inclure des avantages accordés au client. Dans ce cas, par
convention, la précision est abandonnée et on arrondi toujours
arbitrairement le prix de vente à 2 décimales, afin que le produit de la
quantité x prix unitaire corresponde au total affiché (`price`).

#### Réductions manuelles

Les remises manuelles peuvent se faire de deux manières :

-   La manière à privilégier est au niveau d'une ligne de séjour : dans
    le menu déroulant du produit, ajouter une réduction (il est possible
    de renseigner la réduction en pourcentage ou en montant : dans les
    deux cas la valeur est convertie en un pourcentage, affiché dans le
    colonne RÉDUC) ; Note : il n'est pas possible d'accorder une
    réduction de 100% (max 99%).

-   Il est également possible d'ajouter une ligne de produit
    « réduction » : cette pratique permet d'adapter le montant final
    d'un séjour ou d'une réservation pour avoir une valeur arbitraire
    (nécessaire lors de la transition avec des groupes dont les
    avantages liés à l'ancienneté ne s'appliquaient pas). Dans ce cas,
    le produit à utiliser est Remise; il faut alors renseigner une
    quantité de 1 et un montant négatif. Note : il est possible
    d'annuler totalement le montant de la réservation (montant à 0,00
    EUR).
