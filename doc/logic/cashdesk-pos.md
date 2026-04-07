Les utilisateurs n'ont accès qu'aux caisses des centres auxquels ils
sont rattachés et peuvent consulter les détails de la session en cours
et de l'historique de session via les entités Session (qui donnent
accès aux opérations, commandes et paiements).

**La caisse est utilisée pour faire des ventes de deux manières :**

A) la commande correspond à l'encaissement d'un financement d'une
réservation : c'est le seul cas ou un Paiement est créé (ajouté au
financement)

B) le paiement se fait via l'ajout de services dans une réservation
spécifique

Un historique des sessions et des commandes est disponible via `[App
"Point de vente" > Historique]`:

-   L'option "Session" permet de faire la recherche de sessions par
    le libellé de la commande, le centre, l'utilisateur et entre une
    date de début et de fin de la création de la session.

-   L'option "Commande" permet de faire la recherche de commandes par
    le libellé de la commande, le centre, l'utilisateur, le financement
    et entre une date de début et de fin de la création de la commande.
    Dans la liste des résultats, sont présentés l'identifiant, le
    libellé, la date de création, la session associée, le financement,
    le client, le total HT et le total TTC.

## Workflow de la session de caisse

La session de caisse est établie par l'utilisateur et par centre. Elle
comporte deux états : « pending » et « closed ».

<center><img src="/_assets/img/cashdesk-session-workflow.png" /></center>

## Fermeture de la caisse

Lors de la clôture d'une session de caisse, si le montant total de
fermeture incorrect (c'est à dire qu'il ne correspond pas au montant
d'ouverture sur lequel sont appliqué les rentrées et les sorties),
alors une alerte est générée pour indiquer que la caisse a été fermée
avec un montant erroné.

Dans ce cas, une opération de sortie de caisse est également créée pour
ajuster la différence entre le montant de clôture attendu et celui
effectivement enregistré, afin de maintenir la caisse dans un état
cohérent.

<center><img src="/_assets/img/cashdesk-closing.png" /></center>

## Affichage des prix TVAC

Les prix à l'unité sont affichés TVAC. En cas de modification par
l'utilisateur, la valeur encodée est réputée être TVAC et le prix HTVA
est calculé sur base de la TVA associée au produit.

-   Le taux de TVA est toujours pris en compte

-   Le prix HTVA a une précision de 4 décimales

-   C'est toujours le prix TVAC (arrondi) qui est affiché

Remarque : La somme des prix TTC des lignes peut ne pas correspondre au montant TTC de la commande, car la TVA est calculée sur les totaux par taux et non ligne par ligne.

## Paiement des réservations en caisse

La caisse permet de payer des financements. Ceci se fait via :
Application `Caisse > Sessions en cours`

-   Dans le panneau latéral droit, utiliser le bouton caisse pour aller
    sur l'écran de caisse.

-   Cliquer sur Nouvelle commande

-   Dans l'onglet réservations, rechercher la réservation concernée
    pour afficher ses éventuels financements.

-   Sélectionner le financement qui est payé via la caisse (par défaut
    le « montant dû » renseigné est celui du financement, mais il peut
    être modifié)

-   Sur le pavé numérique, sélectionner "Paiement"

-   Valider le paiement et les éventuels moyens de paiement (par défaut
    "Espèce", mais peut être modifié en "Carte"

-   Valider les encaissements et finaliser la commande

Un paiement est alors ajouté au financement correspondant. Si le total
payé correspond au montant dû, le financement est marqué comme payé.

## Paiement des produits en caisse

La caisse permet de vendre des produits. Ceci se fait via : `Application
Caisse > Sessions en cours`.

Le produit vendu peut être ajouté à une réservation spécifique en
choisissant le numéro de la réservation, ou peut être vendu au client de
passage du centre. Les produits associés au client de passage ne seront
pas liés à une réservation, mais seront traités via la facture de vente
au comptoir.

<center><img src="/_assets/img/cashdesk-products-payments.png" /></center>

## Découpe en paiements et encaissements

La caisse permet de découper une commande en plusieurs paiements.

L'écran de droite permet de sélectionner les produits (partie droite) à
mettre sur le paiement en cours (partie gauche).

Pour mettre un produit sur un paiement il faut utiliser l'écran de
droite, sélectionner le ou les produits à ajouter sur le paiement (en
utilisant les cases à cocher), et cliquer sur le bouton "ajouter"
(bouton rond avec "+").

Par défaut c'est le montant total du produit qui est ajouté, pour
paiement en espèces. Mais ceci peut être modifié : à la fois pour le
mode de paiement, et le nombre d'encaissements (il est possible
d'encoder une partie du paiement en espèces et une autre partie par
carte).

Cas particuliers :

-   Il est possible de mettre une commande sur une réservation. Dans ce
    cas, rien n'est encaissé directement et un groupe de services
    supplémentaires est créé dans la réservation renseignée. Le client
    de la commande est automatiquement mis à jour sur base du client de
    la réservation sélectionnée.

-   Lorsqu'elle a été clôturée, il reste possible de modifier une
    commande tant que la facture sur laquelle elle a été mise n'a pas
    été émise.

## Feuilles de caisse

### Feuilles de caisse par session

Sur la liste des sessions de caisse clôturées, il y a un bouton qui
permet d'accéder aux détails de la feuille de caisse.

La feuille de caisse contient les informations suivantes :

-   Les détails du montant ouvert, fermé, attendu et la différence à la
    fin de la fermeture de la caisse (basés sur les commandes et les
    espèces).

-   Les détails de toutes les commandes et des mouvements de la caisse.

Il y a également un récapitulatif des paiements des commandes, qu'ils
soient en espèces, par carte bancaire ou par bon d'achat.

Le récapitulatif des mouvements de la caisse, tels que les entrées et
les sorties. Le récapitulatif de la TVA présente une liste de toutes les
TVA traitées.

### Feuilles de caisse consolidée

Dans l'application Point de Vente, sous `Point de Vente > Session >
Consolidé`, il y a le récapitulatif des feuilles de caisse.

Ce récapitulatif quotidien prend en compte les sessions de caisse
clôturées et fournit les détails suivants :

-   Les totaux journaliers par mode de paiement (Voucher, Carte Bancaire
    et Espèces).

-   Les totaux des mouvements (Entrées et Sorties).

-   Les totaux d'ouverture, de fermeture, attendus et la différence,
    basés sur les paiements en espèces.

Par défaut, la liste résultante est vide et il est possible d'effectuer
une recherche avancée avec le centre sur une période. Le champ
"Centre" est obligatoire et doit être renseigné dans la recherche
avancée.

## Configuration d'une caisse

La création d'une caisse se fait via `Configuration > Points de vente > Caisses`

Une caisse est nécessairement rattachée à un Centre (et donc également à
l'équipe de gestion dont dépend le Centre).

En fonction du type d'imprimante utilisée, il est nécessaire de
configurer le PC utilisé (configuration de l'imprimante) et de préciser
le type d'imprimante (iso-a4 ou pos-80) dans la configuration des
paramètres de l'équipe de gestion.

Afin de pouvoir faire des modifications dans le catalogue de caisse, il
est nécessaire d'accorder les permissions au gérant du Centre en
l'assignant au groupe "pos.default.administrator".

Le catalogue et les listes de prix spécifiques à la Caisse sont
disponibles via `Point de Vente > Catalogue > Listes de prix`.

## Tickets et impression

Le choix du format d'impression doit se faire dans la configuration
d'une équipe de gestion

-   Ticket (PoS Printer 80mm)
-   A4

On part du principe que l'imprimante par défaut est définie sur chaque
poste susceptible d'imprimer un ticket.

Note : S'il s'agit d'une tablette ou d'un poste dédié à la caisse,
il est préférable que l'imprimante ticket soit la seule imprimante
installée.

Dans le navigateur, les paramètres d'impression sont prévus pour une
impression "noir et blanc", et sans affichage des graphiques
d'arrière-plan.

-   Ticket
    -   width: 72mm;
    -   padding: 9mm;

-   A4
    -   width: 210mm;
    -   padding: 10mm 69mm;


## Facturation

Plusieurs commandes d’un même client peuvent être regroupées sur une seule facture.
Cela peut poser des problèmes lors du calcul de la TVA à payer. Pour générer une facture valide, un produit d’arrondi TVA est ajouté afin de corriger les écarts liés aux arrondis.
