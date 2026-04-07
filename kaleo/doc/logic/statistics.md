Des rapports concernant l'activité des différents Centres peuvent être
générés sur base de certaines informations utilisées à des fin de
statistiques :

-   Les réservations peuvent être classées en fonction de la catégorie
    tarifaire des clients correspondants ;

-   Chaque produit peut être assigné à une catégorie statistique ;

-   Les réservations peuvent être classées en fonction du « type de
    réservation » qui leur est assigné.

L'application « Statistiques » permet également l'export de statistiques
sous la forme de documents XLS.

## Origine des données

Par convention, dans les écrans statistiques, les réservations
considérées sont uniquement celles qui correspondent aux critères
suivants :

-   N'ont pas été annulées

-   Ne sont pas en devis ni en option

-   Correspondent à la plage de date fournie

-   Correspondent au centre (si précisé)

Les données sont basées sur les services réservés, regroupés par séjour et sur base des unités locatives (de type logement) attribuées.

Les nombres de personnes renseignés correspondent au nombre théorique de
personnes qui ont séjourné, indépendamment des éventuelles gratuités.

Il y a cependant certaines limitations :

1.  Les consommations peuvent être présentes pour les réservations en option ou en devis et ne peuvent donc pas être utilisées. Par ailleurs, les consommations ne permettent pas de faire de répartition par tranche d'âge
    
2.  Le mode d'assignation des unités locatives sur base de la comptabilisation du produit et du nombre de personnes du groupe, induit une imprécision au niveau du nombre de nuitées (par exemple si, pour un séjour de 2 nuits, un logement pour 4 personnes est choisi pour un total de 6 participants, il y aura 2 logements de capacité de 4 assignés à la réservation, soit un total de 16 nuitées au lieu de 12).
    
3.  Les calculs sont basés sur la correspondance entre la quantité et le nombre de nuitées (ou de jours) et les éventuelles variations d'un
    jour à l'autre, en modifiant la quantité, peuvent aboutir à un résultat légèrement différent de la réalité

Pour les statistiques liées au chiffre d'affaires, ce sont les factures et lignes de facture qui sont utilisées. Il peut donc y avoir des
différences dans les valeurs renseignées selon le type de statistique (puisque les réservations, même terminées, ne sont généralement pas
facturées immédiatement ; et que les réservations dans le futur n'ont nécessairement pas encore de facture).

## Liste des STATS

La plupart des contrôleurs de statistiques sont des entités virtuelles :
ils sont responsables à la fois de fournir une liste de résultat et de
recevoir des paramètres pour générer la liste. Les listes contiennent
toujours tous les résultats disponibles (pas de pagination).
<br>
Des vues distinctes permettent d'afficher la liste des résultats (list
view uniquement) et d'afficher les formulaires d'input des paramètres
(form uniquement).

Des filtres avancés sont ajoutés par vue, pour permettre de générer les
différentes variantes de listes nécessaires.

Dans certaines vues, les lignes peuvent être regroupées pour afficher
directement des informations

Tous les écrans de statistiques disponibles sont regroupés dans l'App
"Stats":

-   Chaque entrée du menu correspond à un écran de statistique, avec une
    vue et un modèle

-   Un champ de recherche a été ajouté dans le menu de gauche pour
    filtrer les écrans de statistiques

Note : Il peut y avoir des limitations au niveau de l'affichage
(exemple : somme des colonnes dans les valeurs regroupées). Dans ces
situations, il est recommandé de télécharger un export en .xls et
travailler à partir de ce fichier.

### Stats Réservations

#### Chiffre d'affaires

Les stats Chiffre d'affaires portent sur des
statistiques prévisionnelles avec les informations comptabilisées sur le mois de durant lequel se déroule le séjour de la réservation (sur bas de la date de fin, c'est-à-dire le mois au cours duquel se termine une réservation).

Les montants affichés dans les différentes variantes de chiffre d’affaires sont basés sur les **lignes de factures** (et non directement sur les réservations).

Les valeurs correspondent aux **montants effectivement facturés**, tenant compte :

- des règles tarifaires appliquées (saisons, catégories tarifaires, etc.)
- des remises éventuelles
- des ajustements liés aux produits

Il s’agit donc de montants **nets facturés**, et non de montants théoriques.

⚠️ **Attention :** ces données peuvent différer des montants comptables (ex : BOB), car :

- les statistiques reposent sur la structuration fonctionnelle des produits (catégories statistiques)
- la comptabilité repose sur les comptes comptables et les écritures validées
- 

Dans le menu `App Stats > (menu gauche) > Stats Réservations >
Chiffre d'affaire`, on retrouve toutes les options concernant les chiffres d'affaires facturés, non facturés, théoriques et prévisionnels.

Pour chacune de ces options, il existe une recherche avancée où l'utilisateur doit sélectionner la date de début et la date de fin. Il est également possible de choisir un centre spécifique ou tous les centres.

L'option "tous les centres" prendra en compte tous les centres auxquels l'utilisateur a accès.

Pour le Chiffre d'affaires prévisionnel, il est possible d'exclure les réservations en option en cliquant sur l'option "Options exclues" dans la recherche avancée.

Ensuite, il y a une liste avec les résultats basée sur les paramètres définis dans la recherche. Dans la liste, on trouve le nombre de
centres, le mois et l'année, les totaux concernant les unités, animations, repas, et un total général.


Les différentes colonnes (unités, animations, repas, etc.) sont déterminées sur base de la **catégorie statistique des produits** présents dans les lignes de facture.

En particulier :

- La colonne **« Nuitées »** regroupe les montants liés aux produits dont la catégorie statistique est de type **`SEJ` ou `GITE`**
- Ce regroupement est **indépendant des comptes comptables**



À la fin de la liste, il y a également un total par type.

-   Chiffre d'affaires facturés : prend en compte les réservations avec
    le statut facturé, solde créditeur, solde débiteur et clôturé.

-   Chiffre d'affaires non facturés : prend en compte les réservations
    avec le statut validé, en cours et terminé.

-   Chiffre d'affaires théorique : prend en compte le chiffre
    d'affaires facturé et non facturé.

-   Chiffre d'affaires prévisionnel : prend en compte les réservations
    avec le statut en option et confirmé.

-   Chiffre d'affaires par produit : Par défaut, tous les produits
    vendus sont renseignés et il est possible de préciser un produit
    spécifique. Les informations sont regroupées sur 2 niveaux : équipe
    de gestion, puis par produit, et sont établies à partir des lignes
    de factures. Chaque ligne reprend le nom du produit, la quantité
    totale facturée et le montant total facturé. Le grand total est
    également renseigné au bas du tableau. 
    
    Les regroupements sont effectués sur base des **produits présents dans les lignes de facture**.
    
    ⚠️ Il n’y a pas de correspondance directe avec les comptes comptables : plusieurs produits peuvent être liés à un même compte comptable, et inversement.
    
    Pour une analyse comptable, il est recommandé de croiser ces données avec les comptes associés aux produits.


⚠️ Contenu de la colonne « Nuitées »

La colonne « Nuitées » ne correspond pas strictement aux seules nuitées d’hébergement.

Elle inclut tous les produits :

* appartenant à une catégorie statistique SEJ ou GITE
* présents dans les lignes de facture

Cela peut inclure des produits de type nuitées, mais aussi :

* des frais fixes
* des packs
* des remises

ou d’autres services associés

👉 Exemple (centres GG) :
* Nuitée Cornimont (Entier)
* Frais fixe Cornimont (Entier)
* Nuitée Bastogne entier
* Frais fixe Bastogne entier

etc.

❗ Conséquence

Le total de la colonne « Nuitées » peut :
* être supérieur au chiffre attendu pour les seules nuitées
* inclure des éléments non directement liés à l’hébergement

Cela dépend entièrement de la configuration des produits et de leur catégorie statistique.


#### Contrats & Réservations

La liste des contrats est accessible via l'application Statistiques :
`Stats Réservations > Contrats & Réservations`

Il est possible de réaliser une recherche avancée par centre,
organisation et intervalle de dates. Par défaut, les champs client et
organisation sont vides, et les dates sont comprises entre le premier
jour du mois et le dernier jour du mois en cours.

Cette liste correspond à un listing reprenant toutes réservations
filtrées sur plusieurs critères :

-   Centre

-   Période

-   Statut de confirmation : 'confirmé' ou 'non confirmé'

La liste de résultats est vide par défaut. Il faut choisir un centre ou
une organisation pour effectuer la recherche. Le résultat est une liste
détaillée des contrats avec toutes les infos, par numéro de résa par
ordre chronologique.

#### Nombre de nuitées par tranche d'âge

Il est possible d'effectuer une recherche avancée par centre ou équipe
de gestion pour une période donnée. Par défaut, la liste résultante est
vide. Il faut nécessairement choisir un centre ou l'équipe de gestion
pour obtenir les statistiques.

La liste résultante est regroupée par tranche d'âge, par centre, et par
catégorie tarifaire. La nature client est également renseignée, en plus
de la catégorie tarifaire.

#### Nuitées max Théorique

Dans les STATS - Nuitées Max Théorique par Centre, il est possible de
réaliser une recherche avancée par centre ou pour tous les centres. Par
défaut, l'intervalle de temps est compris entre le premier jour du mois
et le dernier jour du mois en cours.

La liste des résultats est vide par défaut. Il faut choisir un centre ou
tous les centres pour effectuer la recherche. Les résultats seront
présentés par ordre alphabétique.

#### Nombre de nuitées

Cette vue renseigne le nombre de nuitées (c'est-à-dire le nombre de
personnes qui logent dans le Centre) pour chacune des dates de
l'intervalle renseigné.

Les dates de l'intervalle sont incluses. Une recherche avec une date
identique dans les champs "Du" et "Au" renseignera donc le nombre de
personnes qui ont séjournées le soir de la date renseignée.

Une recherche avec un intervalle couvrant les dates 01/03, 02/03, 03/03
renseignera le total du nombre de personnes logeant au Centre aux soirs
de chacune de ces dates.

Il est possible de faire une recherche avancée par centre, "Tous les
centres", et une période donnée. Si l'option "Tous les centres" est
sélectionnée, tous les centres auxquels l'utilisateur a accès sont pris
en compte. Par défaut, la liste résultante est vide, et il est
nécessaire de sélectionner soit un centre spécifique, soit l'option
"Tous les centres".

Attention : le total ne peut pas être comparé avec les assignations
d'unités locatives dans le planning (dans les assignations il est en
effet possible de renseigner un nombre d'occupants supérieur au nombre
réel).

#### Taux d'occupation

Le taux d'occupation est le rapport en % entre le nombre de chambres
occupées et disponibles, par an, groupé par Centre.

Il est possible de réaliser une recherche avancée par centre ou pour
tous les centres. Par défaut, le champ intervalle est mois et les dates
sont comprises entre le premier jour du mois et le dernier jour du mois
précédent.

La liste des résultats est vide par défaut. Il faut choisir un centre ou
tous les centres pour effectuer la recherche.

### Statistiques OTA

Pour chaque Centre, pouvoir récupérer le nombre de réservations qui ont
été réalisées avec chaque OTA partenaire.

-   Animations

-   Caisse : liste des ventes faites en caisse

-   Localisation : coordonnées complète des centres + coordonnées GPS
    (ne change jamais)

-   Nuitées par an : nombre de nuitées, par an, groupé par équipe de
    gestion

-   Capacité gîte : nombre de lits disponibles dans chaque centre (ne
    change presque pas)

-   Nombre de jours ouverts : nombre de jours non-fermés, par an, par
    Centre

-   Nuitées max théorique : capacité maximum théorique en termes de
    nuitées, en fonction des logements disponibles au cours de
    l'année (Note pour les capacités des Centres : on n'a que
    l'information immédiate, on ne conserve pas l'historique dans
    Discope).

### Statistiques de consommation énergétique

Les statistiques des relevés de compteur sont disponibles via `App Stats > Statistiques compteurs`

Il est possible de réaliser une recherche avancée par centre ou pour
tous les centres, et par type de compteur.

Par défaut, l'option "tous" est sélectionnée. L'intervalle de temps
est compris entre le premier jour du mois et le dernier jour du mois en
cours.

La liste des résultats est vide par défaut. Il faut choisir un centre ou
sélectionner "tous les centres" pour effectuer la recherche.

Les résultats sont groupés par centre et en ordre alphabétique, en se
basant sur le numéro du centre et le nom du type de compteur, et avec
les colonnes suivantes :

-   Nom du centre
-   Nom du compteur
-   Type de compteur
-   Unité de mesure
-   Index final
-   Index initial
-   Consommation (delta)

Pour les gîtes de groupes, l'onglet de relevé de compteur est affiché lorsqu'une
réservation a le statut "terminé" (et qu'elle n'est pas annulée).

La liste est groupée par type de compteur et triée par date de création.
La différence entre l'index du check-in et celui du check-out est calculée et affichée pour chaque compteur.
La colonne "Valeur" présente la valeur d’affichage de l'index (avec une virgule séparant les décimales).

#### Coefficient

Pour chaque compteur, il est possible de définir un coefficient (par défaut à 1).

Ce coefficient est utilisé pour ajuster la quantité lors de la création des lignes de réservations, suite à l’encodage de checkout.

Lors du relevé de compteur, le prix est retrouvé à partir du produit, de la date de création du relevé, et de la catégorie de la liste de prix définie dans le centre.

Un groupe de service intitulé "Consommation des relevés" est créé, déclenchée après la validation de l'inspection pour le checkout :

  - Des lignes de service sont créées pour chaque compteur repris dans les inspections. Le prix unitaire est fixé sur base de la date de checkin, et la quantité est calculée comme la différence entre l'index de checkout et celui de checkin, multipliée par le coefficient défini pour le compteur.
  - Pour chaque ligne, le prix correspond au produit de la quantité et du prix unitaire, et le nom du compteur est renseigné dans la description.
  - Le prix de la réservation est mis à jour et les relevés sont marquées comme facturées.
