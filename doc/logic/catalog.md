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

-   **Consommables** : des produits physiques, avec ou sans suivi de
    stock selon leur configuration et les flux utilisés.
    -   Simple : il n'y a pas de suivi formel des consommables simples
        et la gestion du stock n'est pas supervisée.
    -   Stockable : les consommables stockables disposent d'un mode de
        suivi (`none`, `batch`, `sku` ou `upc`). La configuration de ces
        champs ne suffit pas à mettre en place les mouvements de stock
        ou les règles de réapprovisionnement ; leur intégration doit être
        vérifiée dans le flux utilisé.

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

-   Un produit comptabilisé au **logement** tient compte du nombre de
    logements nécessaires selon la **capacité** et le nombre de
    participants. S'il est répétable, cette quantité est multipliée par
    le nombre d'occurrences, généralement les nuits du séjour.

-   Un produit comptabilisé à la **personne** est facturé en fonction du
    nombre de participants et dispose d'un attribut '**durée**' (en
    jours ou nuits) qui permet de générer le planning des services
    associés.

Note : Les nuitées représentent un cas particulier de services
"planifiables" comptabilisés au sein du logement, impliquant
l'occupation d'une unité locative dont le stock est limité.

Les consommations se réfèrent exclusivement à des services planifiables.

Le calcul dépend de `qty_accounting_method`, `is_repeatable`,
`is_accomodation`, `capacity` et des éventuelles quantités ou durées propres.
Pour un service répétable, le nombre d'occurrences repose normalement sur
les **nuits du séjour**, ou les **jours de l'événement**. Une durée propre
peut remplacer cette durée.

| Mode | Calcul courant, avant dérogations |
| --- | --- |
| `unit` | Une unité par défaut ; la durée ou la répétition peut faire varier la quantité, indépendamment du nombre de personnes. |
| `person`, sans répétition | Nombre de personnes concernées. |
| `person`, avec répétition, hors logement | Nombre de personnes concernées × nombre d'occurrences. |
| `person`, avec répétition et logement | Nombre d'occurrences × arrondi supérieur du nombre de personnes / capacité, lorsque la capacité est positive. Utiliser `capacity=1` pour une nuitée par personne. |
| `accomodation` | Nombre de logements nécessaires, arrondi au supérieur selon la capacité ; multiplication par les occurrences si le produit est répétable. |

Exemples :

- Six personnes pendant deux nuits, avec une nuitée de capacité `1` : **12 nuitées-personnes**.
- Six personnes pendant deux nuits, avec une chambre de capacité `2` comptabilisée au logement : **6 nuitées-chambres**.
- Quatre adultes et deux enfants, avec deux petits-déjeuners chacun : **8 petits-déjeuners adultes et 4 enfants**, si les variantes et les affectations d'âge sont configurées.
- Un nettoyage final non répétable, comptabilisé à l'unité : **1 prestation**.

Pour les repas, `schedule_offset` décale le début des consommations ;
ce champ n'active pas à lui seul la répétition.

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

Les lignes d'un pack conservent le mode de comptabilisation de leur
modèle de produit : à l'unité, à la personne ou au logement. Le pack
possède également sa propre configuration de quantité et de prix.

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

Lorsqu'un séjour utilise un pack à prix propre, il est possible
d'ajuster les consommations de manière indépendante (les moments
auxquels les personnes seront effectivement présentes pour les repas,
pour les chambres), mais le prix comptabilisé est celui du pack
(forfait), même dans le cas où certains produits présents dans le pack
ne sont finalement pas "consommés".

Avec `has_own_price=false`, le montant est calculé à partir des lignes
de services. Avec `has_own_price=true`, un prix est défini pour le
produit pack dans une liste de prix. Dans le code actuel, la génération
d'un pack à prix propre verrouille le groupe, et le calcul de quantité
d'un groupe verrouillé renvoie `1` ; il faut donc vérifier le montant
forfaitaire attendu plutôt que supposer une multiplication par les personnes
ou les nuits.

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

Dans le sélecteur `sale_catalog_product_collect-pack`, un pack sans prix
propre peut apparaître sans ligne de prix pour le pack. Un pack à prix
propre doit disposer d'un prix applicable. Dans les deux cas, le pack doit
être vendable et appartenir à un groupe de produits du centre. Les prix
des composants doivent également être configurés pour calculer correctement
les packs sans prix propre.

## Synthèse : modèles, produits et packs

Le **modèle** définit le comportement de réservation commun, tandis que
le **produit** représente la variante sélectionnable. Un pack est lui-même
un produit, dont le modèle porte `is_pack=true`.

| Objet | Configuration portée | Exemple |
| --- | --- | --- |
| `ProductModel` | Type, planification, calcul des quantités, unité locative ou activité, code de regroupement. | Modèle « Petit-déjeuner ». |
| `Product` | SKU unique, prix associés, tranche d'âge, catégorie tarifaire, éligibilité aux gratuités. | « Petit-déjeuner adulte » et « Petit-déjeuner enfant ». |
| Pack | Produit associé à un modèle avec `is_pack=true` ; contenu défini sur `Product.pack_lines_ids`. | « Nuitée et petit-déjeuner ». |
| `PackLine` | Référence à un modèle enfant via `child_product_model_id`, quantité et durée propres éventuelles. | Inclure le modèle « Petit-déjeuner », puis choisir les variantes selon les participants. |

Les prix sont des objets `sale\price\Price` liés au produit et à une liste
de prix. Un même produit peut ainsi avoir différents prix selon la période
et, lorsque le flux le prend en charge, la catégorie tarifaire.

### Types de produits et exemples

Les champs du tableau suivant appartiennent à `ProductModel`, sauf mention
explicite du produit. Il s'agit d'exemples de configuration ; leur présence
dans un catalogue donné dépend des données installées.

| Type | Exemple | Configuration principale | Possibilité illustrée |
| --- | --- | --- | --- |
| Consommable simple | Kit de bienvenue | `type=consumable`, `consumable_type=simple` | Article physique ajouté à la réservation. |
| Consommable stockable | Mug souvenir | `type=consumable`, `consumable_type=storable`, `tracking_type=sku` | Configuration d'un article avec suivi de stock. |
| Service simple à l'unité | Nettoyage final | `type=service`, `service_type=simple`, `qty_accounting_method=unit` | Frais indépendants du nombre de participants. |
| Service simple à la personne | Linge de lit | `service_type=simple`, `qty_accounting_method=person` | Quantité liée au nombre de personnes. |
| Logement par personne et par nuit | Lit en dortoir | `is_rental_unit=true`, `is_accomodation=true`, `qty_accounting_method=person`, `capacity=1`, `is_repeatable=true` | Nuitées facturées par personne. |
| Logement par chambre et par nuit | Chambre double | `is_rental_unit=true`, `is_accomodation=true`, `qty_accounting_method=accomodation`, `capacity=2`, `is_repeatable=true` | Capacité, nombre de chambres et nuits. |
| Unité locative précise | Gîte privatisé | `is_rental_unit=true`, `rental_unit_assignement=unit`, `rental_unit_id` | Réservation d'une ressource déterminée. |
| Location hors logement | Salle de réunion | `is_rental_unit=true`, `is_accomodation=false`, `service_type=schedulable`, `schedule_type=timerange`, `schedule_default_value=09:00-17:00` | Occupation d'une salle sur une plage horaire. |
| Repas répétable | Petit-déjeuner | `is_meal=true`, `service_type=schedulable`, `qty_accounting_method=person`, `is_repeatable=true`, créneau petit-déjeuner | Repas répartis sur un séjour. |
| Repas ponctuel | Panier pique-nique | `is_meal=true`, `service_type=schedulable`, `qty_accounting_method=person`, `is_repeatable=false`, `meal_location=takeaway` | Repas à une date déterminée. |
| Collation | Goûter | `is_snack=true`, `service_type=schedulable`, `qty_accounting_method=person`, créneau après-midi | Gestion séparée des collations. |
| Activité interne | Tir à l'arc | `is_activity=true`, `activity_scope=internal`, `service_type=schedulable`, `has_staff_required=true` | Planification et affectation d'employés. |
| Activité externe | Visite de musée | `is_activity=true`, `activity_scope=external`, `has_staff_required=false`, `has_provider=true`, `providers_ids` | Intervention d'un prestataire. |
| Activité sur une journée | Journée aventure | `is_activity=true`, `is_fullday=true` | Affectations sur les créneaux AM et PM. |
| Transport d'activité | Navette musée | `is_transport=true` | Modèle référencé par `transport_product_model_id` sur l'activité. |
| Matériel d'activité | Location de casque | `is_supply=true` | Location de matériel sous forme de service ; les besoins d'une activité peuvent aussi être définis via `supplies_ids`. |
| Produit de camp | Camp de vacances | `is_camp=true` ; sur `Product`, `camp_product_type=full` | Démonstration distincte du flux d'inscription aux camps. |

Pour les consommables stockables, `tracking_type` accepte aussi `none`
(papeterie sans suivi), `batch` (lots de barres céréalières) et `upc`
(boissons avec code-barres). Aucun usage de ce champ n'a été identifié dans
le calcul des réservations du package `sale` ; ne pas assimiler ces valeurs
à une démonstration complète de mouvements de stock.

Pour les unités locatives, `rental_unit_assignement` propose trois modes :
`unit` pour une unité précise, `category` pour une catégorie d'unités,
et `auto` pour une attribution selon les capacités. Les orthographes
`accomodation` et `rental_unit_assignement` sont celles des identifiants du code.

### Variations possibles

| Dimension | Configuration | Exemple d'utilisation |
| --- | --- | --- |
| Quantité selon l'âge | Sur `Product`, `has_age_range=true` et `age_range_id` | Variantes adulte et enfant d'un même repas. |
| Gratuités | Sur `Product`, `is_freebie_allowed` | Petit-déjeuner éligible ; linge de lit exclu. Une règle de gratuité applicable reste nécessaire. |
| Catégorie tarifaire | Prix standard et prix lié à une catégorie tarifaire | Tarif scolaire d'une activité. Distinguer ce tarif de la restriction portée par le produit. |
| Répétition | `is_repeatable` | Petit-déjeuner quotidien et pique-nique ponctuel. |
| Décalage de début | `schedule_offset=1` | Premier petit-déjeuner le lendemain de l'arrivée. |
| Durée fixe du service | `has_duration=true`, `duration=2` | Service prévu pour deux jours, indépendamment de la durée globale du groupe. |
| Durée de l'activité | `has_activity_duration=true`, `activity_duration=2` | Activité de deux **heures**, distincte de la durée du service en jours. |
| Contraintes opérationnelles | Employés éligibles, `has_rental_unit`, `activity_rental_units_ids`, fournitures, transport ou prestataires | Activité nécessitant un animateur, un espace et du matériel. |
| Activité non facturable | `is_billable=false` | Accueil ou présentation conservés dans le planning. |
| Regroupement documentaire | `grouping_code_id` | Codes « Logement » et « Restauration » ; rendu à vérifier avec les modèles de documents utilisés. |

### Exemples de packs réutilisant ces produits

| Pack | Composition | Configuration | Objectif de démonstration |
| --- | --- | --- | --- |
| Nuitée et petit-déjeuner | Nuitée + petit-déjeuner | `has_own_price=false` ; sur le produit pack, `has_age_range=false` | Addition des composants et variantes de repas selon l'âge. |
| Pension complète | Nuitée + petit-déjeuner + déjeuner + dîner | `has_own_price=false`, `allow_price_adaptation=true` | Répétition et adaptations tarifaires applicables. |
| Week-end famille | Logement + repas + activité | `has_own_price=true`, `allow_price_adaptation=false` | Prix propre du forfait et groupe verrouillé. |
| Journée séminaire | Salle + déjeuner + goûter + kit de bienvenue | `has_own_price=false` ; ligne de salle avec `has_own_qty=true`, `own_qty=1` | Ressource commune et prestations par personne. |
| Séjour découverte | Logement + tir à l'arc + pique-nique + navette | Prix des composants ; dérogations `has_own_qty` ou `has_own_duration` sur certaines lignes | Composants dont la quantité ou la durée diffère de celle du séjour. |

Sur `PackLine`, `has_own_qty` active `own_qty`, tandis que
`has_own_duration` active `own_duration` (en jours). Le champ `share`
définit la part analytique de la ligne. Sur le produit pack, `is_locked`
permet de définir le verrouillage ; un prix propre force également le
verrouillage lors de la génération du groupe. Le cas d'un pack verrouillé
sans prix propre doit être validé séparément avant d'être utilisé en démonstration.
