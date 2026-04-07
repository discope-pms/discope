Du point de vue de la tarification, il y a deux sortes de packs :

1. Des produits avec un prix fixe spécifique, calculés au logement ou à
   la personne, qui regroupent une série de produits ;

2. Templates (bundles) regroupant des produits qui correspondent à une
   formule proposée aux clients : le prix correspond alors à la somme
   des lignes de produits et services.

Packs « bundles » : Pour faciliter le travail des opérateurs qui
prennent les réservations, ces packs sont définis comme template pour
servir de base aux réservations "à la carte".

Points d'attention concernant les packs :

-   Garder une bonne visibilité du catalogue : il faut se limiter à un
    certain nombre de packs (il est convenu de définir un "responsable
    catalogue" en charge de la liste des packs et de leur composition)

-   Il y a un engagement sur le prix de certains packs ("formules"
    présentées dans le catalogue) : il est indispensable de maintenir la
    transparence vis-à-vis du client

-   Pour ajuster les produits ajoutés dans le cadre d'un "pack", on
    doit connaître : le mode de comptabilisation des produits ; le
    nombre de personnes et le nombre de nuits.

Il peut y avoir des avec un prix spécifique (prix du pack différent de
la somme des prix des produits qui le compose) et des "packs bundle"
(prix dépendant des produits qu'il regroupe).

Les Packs font partie du catalogue : à ce titre ce sont des produits à
part entière (avec un SKU). Lorsqu'un produit est un pack, il est
possible de le marquer soit comme ayant son propre prix, soit comme
dépendant du prix des produits qu'il contient.

Les Packs renseignent une liste de modèles de produits. Ces modèles sont
utilisés pour déterminer les produits à utiliser selon la variante qui
s'applique à la situation (principalement la tranche d'âge).

Au niveau des consommations (lignes de réservation), pour pouvoir à la
fois prédéfinir une liste de produits qui sont inclus dans un pack et
permettre d'adapter la liste des produits au moment de la prise de
réservation, on fonctionne par regroupement (groupe de lignes de
services).

Les regroupements sont similaires aux packs (ils regroupent une série de
produits), mais ne pas sont eux-mêmes des produits : il s'agit d'une
sorte de pack virtuel modifiable pour créer des séjours à la carte.

Les regroupements peuvent soit être composés manuellement (produit par
produit), soit être générés sur base d'un pack (les produits du pack
sont automatiquement importés dans le regroupement).

Au niveau logique, il y a toujours un produit par ligne de réservation.

Au niveau de la présentation, on a la possibilité de manipuler les
produits au sein de regroupements.

Ceci permet à la fois :

-   Définir des informations pour l'ensemble d'un regroupement (dates,
    durée/nb_pers, réductions, gratuités, logements)

-   Modifier le contenu de Packs librement (nombre de repas, liste des
    produits [animations])

Pour chaque regroupement, il est possible d'y assigner un intitulé (qui
pourra éventuellement être affiché sur le contrat et la facture).

-   Si le nombre de personnes et les dates de séjour sont les mêmes, il
    est possible de reprendre différents types de chambres à un même
    regroupement (en assignant un nombre de personnes à chaque unité
    locative). Dans le cas contraire, la ou les chambres supplémentaires
    doivent être ajoutées dans un regroupement distinct.

-   Lorsqu'il y a plusieurs plages de dates non-contiguës, les
    prestations doivent être placées dans des regroupements différents
    (afin de ne pas bloquer les UL pour rien entre les plages de dates).

Pour chaque regroupement, il est possible d'appliquer une plage de
dates, à un nombre de personnes (groupe), et à une catégorie tarifaire ;
ces informations fixent les valeurs par défaut pour chaque produit du
regroupement (quantité et réductions).

Par la suite, les modifications dans la partie "consommations"
impactent les quantités renseignées pour les produits du regroupement
(et le prix).

## Tranches d'âges

Les packs peuvent être associés à une tranche spécifique, afin que ce
soit systématiquement la variante de produit correspondant à cette
tranche d'âge qui soit sélectionnée pour les modèles de produits du
pack.

> Il est possible de forcer le 'age_range_assignement' en associant
  une tranche d'âge exclusive à un pack.

> On utilise cette fonctionnalité pour les packs Séjours Scolaire, pour
  lesquels les prix appliqués sont les prix des tranches d'âge enfant.
  Chaque pack de Séjour scolaire, est lié à sa section référence
  (maternelle, primaire-secondaire).

> Par contre la ventilation des tranches d'âge des participants est
  maintenue (pour pouvoir, entre autres, appliquer les gratuités et 
  établir des statistiques par tranche d'âge).

En fonction des tranches d'âges sélectionnées, pour les lignes
comptabilisées à la personne :

-   S'il n'existe qu'un seul produit (tous les âges ou enfants seuls): 
    une seule ligne est créée

-   S'il existe plusieurs produits, on crée une ligne par tranche
    d'âge sélectionnée (ventilation automatique des quantités)

### Logique de fonctionnement pour les modifications de tranches d'âge

Lorsqu'on crée une nouvelle tranche d'âge, la valeur assignée par
défaut est 1. Lors de l'ajout d'une tranche d'âge, le nombre de
personnes du séjour est automatiquement incrémenté de 1.

Si, pour une ligne de tranche d'âge, la tranche d'âge est modifiée
(mais pas la quantité), la quantité reste à un nombre identique.

Une fois que plusieurs tranches d'âges ont été créées :

-   Il n'est pas possible de choisir un nombre de personne (pour tout
    le séjour) qui soit supérieur à la somme des personnes pour chaque
    tranche d'âge
-   Si on supprime une tranche d'âge, le nombre de personnes du séjour
    est automatiquement adapté
-   Si on augmente le nombre de personnes assignées à une tranche
    d'âge, le nombre de personnes du séjour est automatiquement adapté
