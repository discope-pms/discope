## Logique de tarification

La logique de Discope permet à l'organisation de générer la
tarification sur la base de la catégorie tarifaire des clients.

Une liste de prix de base est définie annuellement. Chaque centre peut
avoir une liste de prix spécifique, qui surcharge la liste globale.

En plus de cette logique, pour les produits n'impliquant des
prestataires externes (c'est-à-dire uniquement les repas et les
nuitées), les prix finaux sont établis en appliquant, pour chaque
catégorie tarifaire, une série de réductions/adaptations en fonction de
la saison, de la durée du séjour, de la taille du groupe et de la
fidélité.

-   Cette approche diffère significativement de la gestion
    traditionnelle des catalogues de prix.

-   Du point de vue organisationnel, il n'est pas nécessaire de montrer
    le détail de ce calcul aux opérateurs, mais du point de vue
    fonctionnel, c'est le plus efficace et l'approche qui permet la
    meilleure systématisation

-   Le seul moyen de visualiser ce prix final est lors des réservation
    (le prix dépend du client, des dates de séjour, du nombre de
    personne)

Les paramètres qui influencent la tarification sont :

-   Le centre (il y a une volonté d'homogénéisation mais il est
    possible que le prix d'un produit varie d'un centre à l'autre)
    =\> permet l'identification de la **catégorie de liste de prix**

-   Les tarifs de l'année en cours =\> permet l'identification de la
    l**iste de prix**

-   L'âge de la personne bénéficiaire d'un bien ou d'un service (qui
    n'est pas nécessairement le client à l'origine de la réservation)
    =\> permet l'identification de la **variante du produit** et du
    **prix final**

Note : Le tarif à appliquer doit être le tarif valide lors du premier
jour du séjour (même lorsque à cheval sur 2 saisons).

## Catalogue

Une liste de prix est définie annuellement (s'il n'y a pas de
changement, soit la durée de validité de la liste est prolongée, soit la
liste est dupliquée).

Il est possible de définir plusieurs listes de prix. Les listes de prix
sont regroupées par centre.

-   Un tarif par nuit est assigné à chaque unité locatives, ainsi que
    pour les nuitées avec pension (qui forment des Packs)

-   Les tarifs sont tous renseignés TVAC (le montant de la TVA peut
    varier selon les Centres et le type de produit)

Les produits sont organisés par SKU (équivalent aux mnémoniques
actuels). Les SKU sont invariables d'une année à l'autre et servent
d'identifiants pour y associer les tarifs. Les produits sont déclinés
sur base du critère âge (chaque produit a donc au minimum 1 SKU et au
maximum autant qu'il y a de catégories d'âge)

Les produits sont assignés à :

-   Une famille de produits (pour permettre la distinction des produits
    au niveau du centre auquel ils sont rattachés)

-   Un ou plusieurs groupes de produits (pour faciliter leur recherche
    au sein du catalogue d'un centre)

-   Une ou plusieurs catégories de produits

-   Une règle comptable de vente

## Listes de prix

Le catalogue est modélisé par des familles de produits et par des listes
de prix.

Le prix de vente correspondant à chaque produit (SKU) est toujours
établi sur base de **listes de prix** (ou "grilles tarifaires"), dont
la durée de validité est variable.

La grille tarifaire reprend plusieurs paramètres :

-   Nom : un libellé permettant d'identifier facilement la liste

-   Date_from, date_to : les tarifs peuvent varier d'une année à
    l'autre, administrative ou calendaire

-   Catégorie : la catégorie de grille tarifaire à laquelle est
    appartient

Les liste de prix ont un statut qui est assigné manuellement.

Seule les listes dont la période couverte est dans la future (date_to \>
now) et qui sont à l'état 'publiée' peuvent être candidates pour
l'assignation des prix d'une réservation.

Il peut y avoir plusieurs listes candidates, mais il n'y a toujours
qu'une seule liste éligible (celle qui couvre la plus courte période
reprenant une plage de dates donnée).

Lorsqu'une liste ne doit plus être utilisée, on peut modifier son
statut et le mettre à 'en pause' (temporaire) ou 'clôturée'
(définitif).

-   Les listes candidates ont donc toujours le flag 'is_active' à
    vrai, et peuvent être consultées via `Configuration > Ventes >
    Catalogue > Prix > Listes actives`

-   Les listes de brouillon ont le statut 'à confirmer', et peuvent
    être consultées via le même menu `Listes brouillon`

-   L'historique complet des listes est accessible via `Toutes les
    listes`

**Création de listes de prix**

Pour créer de nouvelles listes de prix, le plus simple est de cloner les
listes de prix existantes pour l'année la plus avancée déjà encodée. Il
faut alors modifier le nom (qui, par convention, renseigne l'année),
les dates de début et de fin, ainsi que le statut ('à confirmer').

Lorsque les prix pour une année donnée sont validés, il suffit alors de
mettre les prix à jour pour chacune des listes concernées et de marquer
celles-ci comme "publiées".

## Réservations anticipées

Pour mettre en option une réservation, il faut obligatoirement que son
montant soit non-nul. Or, les prix étant retrouvés dynamiquement, si
aucune liste ne correspond aux dates de la réservation, le montant de
celle-ci restera à zéro.

La solution qui a été mise en œuvre est de permettre de créer des listes
prix pour les années futures (pas de limite dans le temps), et de les
marquer comme brouillon (statut "à confirmer").

De cette manière le système est capable de trouver des prix et accepte
la mise en option des réservations concernées.

Dans le cas où elle utilise une **liste de prix à confirmer**, lorsque
la réservation est mise en option, elle est également marquée « à
confirmer » (avec un flag `is_price_tbc`), et dans le devis une
mention est ajoutée en ce sens ("Le prix est renseigné à titre
indicatif et est susceptible d'évoluer").

-   Afin de maintenir la cohérence dans la logique applicative,
    lorsqu'une réservation est marquée `is_price_tbc`, il n'est pas
    possible de la confirmer (les financements / plan de financement
    sont basés sur un prix qui n'est pas confirmé - et le plan de
    financement n'est pas regénéré lorsque la liste est validée - ; et
    le contrat n'est pas généré, or il doit toujours y avoir un contrat
    pour une réservation confirmée).

-   Lorsqu'une réservation avec prix « à confirmer » est mise en option,
    l'option est automatiquement sans date d'expiration. Si le client
    confirme : une note est ajoutée à la description de la réservation
    en attendant la validation de la liste de prix. Si le client renonce
    à l'option, la réservation doit être remise en devis et archivée.

-   Lorsqu'une liste de prix est publiée, les prix de toutes les
    réservations impactées sont réinitialisés, le flag `is_price_tbc`
    est retiré, et une notification est émise : l'utilisateur peut
    alors passer la réservation en "confirmée".