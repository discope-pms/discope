## Gestion de l'année comptable/fiscale en cours

### Sequence

La facturation se fait par équipe de gestion. Il y a donc une séquence
par équipe de gestion et par année.

finance.invoice.fiscal_year : année comptable en cours
<br>
lodging.invoice.sequence.{center} : séquence de facturation pour une
équipe de gestion pour l'année comptable en cours

-   On ne peut pas émettre une facture sur une année clôturée

-   L'année utilisée pour une facture correspond à celle de sa date
    d'émission (on ne peut jamais émettre une facture à une date
    antérieure à celle de la facture émise la plus récente, quelle que
    soit l'année)

-   Il n'est pas possible d'émettre une facture pour une autre année
    que l'année fiscale en cours. En d'autres termes, on ne peut pas
    émettre de facture sur une nouvelle année tant que l'année fiscale
    précédente n'est pas clôturée.

### Action "clôturer l'année comptable"

Cette action met à jour le paramètre finance.invoice.fiscal_year et
l'incrémente de `1`. Pour chaque équipe de gestion, les séquences de facturation sont
réinitialisées à 1 pour la nouvelle année comptable.

Une tâche est planifiée chaque année au 15/01 pour exécuter cette
action.
Cette date est arbitraire et peut être modifiée si nécessaire: il
s'agit de la date de clôture d'un exercice annuel, définie par
l'organisation (date butoir après laquelle il n'est plus possible
d'émettre une facture pour l'année précédente).

### Numéro de facture

Le numéro de facture est basé sur la date de la proforma et la séquence
de facturation de l'équipe de gestion correspondante.\
La date d'une proforma peut être modifiée à tout moment.

-   Si la date est antérieure à celle de la facture la plus récente,
    c'est la date de la facture la plus récente qui est utilisée.

-   Si la date de la facture ne correspond pas à l'année fiscale en
    cours, on ne permet pas d'émettre la facture (changer le statut),
    et celle-ci reste alors en proforma.

## Notes sur la précision des montants

La logique suivie pour la précision des montant (nombre de décimales)
intervenant dans les réservations et les factures est la suivante :

-   Pour les lignes (réservation, facture) on doit garder la précision
    de 4 décimales au niveau du **total**, afin que le produit du total
    et de la TVA corresponde au prix affiché pour la ligne (dans le cas
    de qty 1 le prix doit correspondre au prix catalogue)

-   Pour les lignes (réservation, facture), il est nécessaire de
    maintenir une précision de 4 décimales pour le **prix unitaire**
    afin que le total affiché corresponde au produit de la quantité, du
    prix unitaire et de la TVA

-   Le **total d'une facture** correspond à la somme des arrondis des
    totaux (précision 2) de ses lignes

-   Le **total d'un groupe** de lignes correspond à la somme des
    arrondis des totaux (précision 2) de ses lignes

-   Le **total d'une réservation** correspond à la somme des arrondis
    des totaux (précision 2) de ses lignes

-   Dans le cas où le prix unitaire est adapté (suite à l'application
    d'avantages), la précision est abandonnée (on arrondit à 2
    décimales), afin que le produit de la quantité x prix unitaire
    corresponde au total affiché (`price`)

## Distinction Proforma et Facture

Une « Proforma » est un brouillon de facture qui n'a pas de valeur
comptable et est simplement une indication qu'il est possible de
transmettre au client pour renseigner sur ce qu'il est (au moment de la
génération de la proforma) prévu de facturer.

Les proforma sont générées dans 4 situations :

-   Lors de la demande de conversion d'un acompte en facture

-   Lors de l'application d'un plan de paiement qui spécifie la
    création d'une facture (à la confirmation)

-   Lors du passage de la réservation de l'état "terminée" à
    "facturée"

-   Lors de l'annulation d'une facture (création d'une note de
    crédit)

En cas d'aller-retour entre les états "terminée" et "facturée", la
proforma de solde, si elle existe déjà, est toujours remplacée par la
nouvelle.

Une note de crédit proforma ne peut pas être supprimée, cela causerait
une incohérence dans la suite des factures. Une facture annulée se
retrouverait sans ça note de crédit indispensable.

Important :

-   Une proforma n'est pas une facture mais un brouillon de facture, et
    peut donc toujours être supprimé (via la fiche de la proforma,
    `Actions > Supprimer la proforma`).

-   Lorsqu'une proforma est générée, il ne faut pas systématiquement la
    convertir en facture (parfois c'est simplement un brouillon
    indicatif, parfois il faut attendre que d'autres réservations
    antérieures soient facturées)

Avant d'émettre une facture (quelle qu'elle soit : facture d'acompte,
note de crédit, ou autre), il faut que les factures du mois précédent
aient été émises.

Les financements ne sont créés que lorsqu'une facture est émise (il
n'y a donc pas de financements pour les proforma).

Si un paiement est reçu alors que la facture n'a pas encore été émise,
il est recommandé de créer manuellement un financement et de l'associer
à la ligne d'extrait ou au paiement en caisse.

Lors de la création de la facture de solde, le financement sera pris en
compte pour établir le montant restant dû.

## Créer une facture de solde

Lorsqu'une réservation passe à l'état « terminée », il est alors
possible de créer une facture de solde. Les factures passent par
plusieurs états : brouillon de facture (proforma) et facture émise.

La création de facture se fait donc en deux étapes :

1.  Sur la fiche de réservation, utiliser le bouton d'action
    (« ACTIONS » à droite du statut de la facture) et sélectionner « FACTURER LE SOLDE ».
    Un brouillon de facture (proforma) est alors créé. En cas d'erreur, ce
    brouillon peut être mis à jour en repassant la réservation à l'état
    "terminée" (toujours via le bouton à droite du statut de la réservation).
    <br>
    Lorsque le brouillon proforma est correct (tous les services ont bien 
    été ajoutés), on peut émettre la facture.

2.  Sur la fiche de la facture proforma (brouillon), utiliser le bouton
    d'action (« ACTIONS » à droite du statut de la facture) et
    sélectionner « ÉMETTRE LA FACTURE ».
    <br>
    Une facture de solde est alors émise et un numéro (définitif) lui est
    attribué. Si un solde reste dû, un financement est également créé et
    rattaché à la facture de solde.

Si une TVA s'applique, alors le client de la facture de solde ne peut pas être
différent de celui utilisé pour les autres factures intermédiaires de la réservation.

## Logique de facturation

Par convention, les factures sont sauf exception), émises au cours du
mois auquel a eu lieu la réservation facturée.

Lors de la création du contrat, les lignes de réservation sont marquées
comme « contractuelles » (is_contractual=true). Le total de la
réservation correspond alors à la somme des prix des lignes.

Si des consommations sont ajoutées en cours de séjour, les lignes
correspondantes sont marquées selon ce qui est convenu avec le client.

-   À ajouter à la facture : le mode de paiement est par facture et le
    statut est non payé (par défaut).

-   Payé directement (en caisse) : le mode de paiement est en espèces et
    le statut est payé.

-   Offert : le mode de paiement est gratuit et le statut est payé.

Les factures d'acompte sont basées sur le total de la réservation au
moment du contrat (généralement un pourcentage du total). Lors de
l'édition de la facture de solde, les lignes marquées comme non payées
sont ajoutées comme lignes de factures.

Le total à payer correspond alors à la somme totale des consommations de
la réservation soustraite des acomptes payés et des sommes déjà perçues
(éventuellement hors acompte).

### Acomptes

Il y a une distinction entre les acomptes facturés et les avances
("prépaiements" ou acomptes non facturés). Le paiement des factures
d'acompte se fait de la même manière que les paiements des factures de
soldes.

Lors de l'émission de la facture de solde, le montant restant dû
(demandé au client) correspond à la somme des services, déduites des
montant déjà payés.

Par contre le montant correspondant de la facture, du point de vue
comptable, correspond au total des services, déduit uniquement des
acomptes facturés. Les avances et autres montants perçus ne génèrent pas
d'écriture comptable.

## Règles comptables

Des règles comptables permettent d'associer les produits à des règles
comptables pour les opérations de vente et d'achat (la gestion des
achats n'est pas prise en charge pour le moment).

Les règles comptables servent, entre autres, à déterminer le régime de
TVA. Il existe autant de règles comptables que d'activités générant une
vente (biens et services). Chaque activité peut être soumise à des
règles TVA différentes.

Une règle comptable permet d'assigner une ou plusieurs lignes
d'imputation à un produit :

-   Label (mémo pour identification)

-   Compte comptable à imputer

-   Section analytique correspondante

-   Règle TVA associée

-   Part de ventilation pourcentage : plusieurs lignes peuvent être
    définies et au produit peut impacter plusieurs comptes comptables.
    La seule contrainte est que la somme des valeurs de ce champ pour
    une règle comptable donnée totalise 100%.

## Ecritures comptables et facturation

**Créances** (actif, débit) => à recevoir

**Dettes** (passif, crédit) => à payer

Lorsqu'on vend un produit, la ligne de facture associée renseigne le
compte qui correspond à la vente avec le compte renseigné dans la règle
comptable associée au produit.

Sur la facture, les acomptes sont comptabilisés avec des quantités
négatives.

Pour chaque facture, des écritures comptables sont générées sur base des
lignes de la facture (il peut y avoir plusieurs écritures par ligne).

Ces lignes sont générées lorsque la facture est émise (passage de
'proforma' à 'invoice').

**Accounting_entry**

Pour chacune des lignes, récupérer la règle comptable et générer une ou
plusieurs lignes d'entrées

```
name (label)
account_id
debit
credit
journal_id
```

Au niveau des écritures comptables, le total des colonnes débit et
crédit doivent être égaux.

**En cas de facture d'acompte, la facture de solde doit mentionner l'acompte**

-   Le montant HT reprend le total soustrait des acomptes déjà facturés

-   Les différentes rubriques de TVA reprennent la somme des TVA dues en
    fonction des classes de TVA s'appliquant aux produits

-   Le total correspond à la somme du montant HT et des rubriques TVA

**S'il n'y a pas de facture, les montants sont considérés comme des pré-paiements.**

-   La facture de solde reprend un champ "déjà payé"

-   Le montant dû (solde) correspond au prix final soustrait des
    prépaiements

## Notes de crédit

Il est possible de créer des notes de crédits (« extourne facture »)
pour annuler des factures émises erronément.

*Note : Les notes de crédit suivent la même séquence que les factures.*

Dans le cas où une facture a été émise mais qu'on se rend compte que le
montant est significativement erroné et qu'il faut adapter le montant
facturé, il y a deux options : 

-   Soit on annule la facture en émettant une note de crédit

-   Soit on crée une nouvelle facture en créant une nouvelle réservation
    (fictive) dans laquelle on ne met que les suppléments à facturer au
    client concerné.

Pour créer une note de crédit après facturation, il faut :

-   Sur la ficher de réservation, sous l'onglet « Factures », aller sur
    la fiche de la facture à annuler, puis utiliser le bouton d'action
    (« ACTIONS » à droite du statut de la facture) et sélectionner
    « NOTE DE CRÉDIT »

Cette action créera une note de crédit annulant le montant de la facture
erronée. La facture initiale sera alors marquée comme annulée.

Il est alors possible repasser la réservation en "terminée" pour
ajouter ou retirer des services (services supplémentaires uniquement).

## Créer une facture d'acompte à partir d'une ligne de financement

Les demandes de paiement (financements) peuvent être utilisées pour
créer une facture.

-   Créer une nouvelle facture avec une seule ligne:

-   Modifier la ligne de funding (type='invoice' et invoice_id)

## Modélisation des écritures comptables

Des écritures doivent être journalisées pour les factures et pour les
opérations de caisse.

Il y a deux types d'opérations de caisse :

**Ventes (order)**

-   Facture :

    -   Traitement identique aux factures de réservation (création auto
        des écritures comptables)

-   Simple ticket (pas 'invoice')

    -   Utilisation du order et orderlines pour générer des écritures
        comptables

    -   On crée des écritures comptables rattachées au Order

**Mouvements (in, out)**

Pour les achats avec l'argent de la caisse, on génère un journal de
caisse à transmettre à la compta (voir plus bas).

Seules les différences de montant doivent être enregistrées comme OD
(entrée ou sortie non justifiée).

On utilise des objets spécifiques aux écritures comptables et aux
exports pour logiciel comptable.

## Écritures comptables

Les écritures comptables sont matérialisées par des objets génériques
"Accounting entries". Les écritures comptables sont toujours assignées
à un journal comptable et peuvent se rapporter :

-   A une facture (ou note de crédit)

-   A une commande de caisse

-   Être indépendantes (OD = régularisations)

### Recherche avancée par ligne de facture

Dans la liste `App Ventes > Factures > Toutes les lignes`, sont
reprises les lignes de factures avec le numéro de ligne, le prix
unitaire, la quantité, la quantité gratuite, la remise, la TVA, le prix
hors TVA et le prix avec TVA.

Il est possible de faire une recherche avancée par équipe de gestion,
organisation, destinataire, produit et une période donnée.

### Générer les exports et les télécharger

Les exports sont générés à la demande. Pour solliciter un nouvel export,
utiliser l'application « Compta ». Dans le menu latéral gauche, aller
dans `EXPORTS > EXPORTS Á TÉLÉCHARGER`.

Si tous les exports ont déjà été téléchargés, la liste est vide. Pour
solliciter la génération de nouveaux export, utiliser le bouton d'action
« EXPORT ». L'opération prend généralement plusieurs secondes.

(Note : seuls les fichiers contenant au moins une ligne sont générés.
Pour les Centres sans factures ou sans paiements depuis le dernier
export, aucune archive n'est générée.)

Les archives peuvent alors être téléchargées individuellement, en
ouvrant leur fiche respective et en utilisant l'action TÉLÉCHARGER.
Note : Une fois téléchargé, un export est disponible dans le menu, via
EXPORTS \> TOUS LES EXPORTS.

Les exports correspondent à des archives .zip, il est nécessaire de le
décompresser pour avoir accès aux différents documents.

## Suivi comptable

Un plan comptable est défini, ainsi qu'un plan analytique.

Afin d'être intégrés à la comptabilité, il est possible d'exporter les
opérations de paiements soit pour tous les centres (avec une colonne se
rapportant à la section analytique de chaque centre), soit séparément en
faisant la distinction entre les Centres auxquels se rapportent les
opérations exportées.

## Paiements

Les paiements sont encodés par les gérants (personnes responsables) de
chaque Centre.

Les paiements sont en principe toujours liés soit à un **acompte**, soit
à une **facture** et leur encodage est réalisé soit par un opérateur,
soit par la caisse.

Lorsqu'un opérateur reçoit un extrait, il peut l'importer et le
réconcilier en imputant chaque ligne de transaction à un ou plusieurs
paiements. Il est possible d'imputer une transaction à plusieurs
paiements. Certaines transactions peuvent avoir déjà été prises en
comptes (c'est le cas pour les paiements à la caisse avec carte
bancaire).

La liste des paiements peut être exportée pour synchronisation avec le
logiciel comptable (comme les logiciels comptables nécessitent la
création préalable de comptes clients, 2 exports sont possibles : 1) les
nouveaux comptes clients 2) les paiements reçus des comptes clients).

## Import / Gestion des paiements (clients)

A l'import d'un extrait :

1\) charger tous les extraits (+ lignes)

2\) faire les résolutions de paiement

3\) quand un extrait est réconcilié.

## Résolution automatique des paiements

Pour la résolution des paiements, on tente d'identifier le financement
concerné en fonction de la communication et du montant (on utilise toute
référence trouvée : txt ou SCOR/VCS).

1. On cherche une correspondance exacte (communication et non-payé)
2. Si on trouve une correspondance pour la communication pour un
    financement déjà payé

    - On cherche une correspondance au niveau du montant parmi les financements de la même réservation qui n'ont pas encore été payés
    - S'il n'y a pas de correspondance, le versement est marqué « à rembourser »

Si un financement est trouvé, la ligne est marquée comme réconciliée.

Si on ne parvient pas à déterminer le financement, l'opérateur doit
utiliser la communication et le nom pour identifier la réservation

-   Si le client a payé trop peu, on continue d'attendre le solde (le
    montant restant dû est affiché/mis à jour)

-   Si le client a trop payé, il faut faire un remboursement (liste des
    remboursements à faire)

Il y a plusieurs limitations :

-   Le montant de la ligne d'extrait et les montants des paiements créés
    manuellement doivent avoir un signe identique (positif ou négatif).

-   En cas de réconciliation manuelle, une fois tous les paiements
    créés, il est nécessaire d'utiliser le bouton d'action "réconcilier"
    pour valider la situation et marquer la ligne d'extrait (et
    éventuellement l'extrait parent) comme réconcilié.

## Récapitulatif de la résolution des paiements


Si la ligne d'extrait contient une communication (c'est-à-dire que le
numéro de communication se retrouve dans la ligne d'extrait, que ce
soit dans le champ communication structurée/VCS ou dans le champ
communication texte),

-   Alors une tentative de réconciliation automatique est réalisée.

-   Sinon la réconciliation doit être faite manuellement.

Une tentative de réconciliation automatique est réalisée sur base de la
logique suivante :

-   S'il existe un financement qui correspond à la communication ET qui
    n'est pas encore entièrement payé

-   S'il existe un financement pour la même réservation (mais pas
    nécessairement la même communication) qui n'est pas encore
    entièrement payé

Alors ce financement est sélectionné et le paiement y est assigné à
100%.

Sinon la réconciliation doit être faite manuellement

Une ligne d'extrait peut être décomposée en plusieurs paiements (qui
sont éventuellement associés à des financements distincts), et un
financement peut être associé à plusieurs paiements (provenant
éventuellement de lignes d'extraits distinctes).

Par contre, l'assignation d'un paiement à un financement se fait
toujours entièrement.

Si le montant versé est supérieur au montant dû, il s'additionne au
total reçu pour la réservation et la régularisation sera faite au moment
de la facturation de solde de la réservation.

Au moment de la facturation de solde, si un financement présent un
montant payé est supérieur au montant dû, la différence est marquée à
rembourser.

Récapitulatif de l'utilisation de l'interface utilisateur :

-   On peut cliquer autant de fois que souhaité sur le bouton
    "Réconcilier" : si une réconciliation peut être faite par le
    système, elle l'est et la ligne passe alors à 'réconciliée' et
    l'action n'est plus disponible

-   On peut faire une réconciliation manuelle : dans ce cas, on crée un
    paiement en l'associant à un financement. Les erreurs sont
    possibles et on peut ajouter supprimer des paiements autant de fois
    que nécessaire. Lorsque la situation semble ok, on clique sur
    "Réconcilier".

-   Dans le cas où on fait une réconciliation partielle (on assigne une
    partie du montant de la ligne à un financement), la réconciliation
    automatique ne fonctionne pas et il faut alors nécessairement
    réconcilier manuellement la ligne en créant tous les paiements
    nécessaires.

## Caisse

Les paiements des différentes consommations peuvent se faire via la
caisse (séjours, boissons, repas).

(Il est également possible de faire des réservations directement via la
caisse.)

Les encodages de caisse se font via un écran spécifique qui permet de
créer des opérations de caisse.

Chaque opération est assignée à une personne (barman ou autre) et à une
caisse (il peut y avoir plusieurs caisses par Centre); et est payée
immédiatement, soit en espèces, soit par transfert bancaire (bancontact,
carte de crédit).

Le livre de caisse de chaque caisse est tenu avec, pour chaque jour, le
montant contenu dans la caisse.

En fin de journée, le gérant effectue la clôture de caisse en comparant
le montant de caisse avec celui de la veille, imputé du delta journalier
(différence entre les montants reçus en espèce et les montants rendus en
espèce).

Les mouvements de caisse (décaissements et approvisionnements) sont
enregistrés pour pouvoir être exportés et synchronisés avec le logiciel
de comptabilité.

## Etapes pour la facturation en fin de séjour

Les factures ne sont jamais émises automatiquement et l'émission d'une
facture est une étape dont la responsabilité relève toujours de
l'utilisateur.

A différents moments, il est possible qu'une facture proforma soit
générée. Une proforma n'EST PAS une facture : un nom plus correct
serait "brouillon de facture".

-   Les factures proforma ne doivent donc PAS TOUJOURS être converties
    en facture.

-   Une fois émise, une facture ne peut plus être modifiée ; elle peut
    uniquement être annulée moyennant l'émission d'une note de crédit.

-   Il est toujours préférable d'émettre la facture d'une réservation le
    plus tard possible (pour éviter des ajustements comptables).

### Statut terminée

Lorsqu'on fait le checkout, la facture passe à l'état "terminée".

A cette étape, il est possible d'ajouter des suppléments (groupes de
services supplémentaires) :

-   En renseignant des quantités positives pour ajouter des produits et
    services ;

-   Ou en renseignant dans quantités négatives dans le cas où on a
    comptabilisé des services en trop.

Une fois tous les services ajoutés, on peut passer à l'étape suivante
via l'action "facturer le solde".

**Sur la fiche de la réservation :** passer la réservation à l'état
« facturée » (via ACTIONS \> FACTURER LE SOLDE).

La facture passe alors à l'état "facturée", qu'il faut interpréter
comme "prête pour émettre la facture".

### Statut facturée

A cette étape, la réservation dispose d'une facture proforma qui a été
générée automatiquement. Si une facture proforma existait déjà au
préalable, elle aura été supprimée et remplacée par une facture proforma
reprenant la liste des services identique à celle de l'écran « Services
réservés ».

Il est encore possible de vérifier le contenu de la facture proforma
pour s'assurer que rien n'a été oublié.

**Sur la fiche de la facture proforma** : cliquer sur « Imprimer /
Envoyer ».

Il est également possible de modifier la date de la facture proforma
(par exemple pour la mettre au dernier jour du mois précédent).
Attention : lors de l'émission de la facture, si la date renseignée
précède celle de la dernière facture émise, c'est la date d'émission
de la dernière facture qui est utilisée.

**Sur la fiche de la facture proforma** : éditer la facture pro-forma
(via MODIFIER), modifier la date et sauver (via SAUVER & VOIR).

Si des services sont manquants, il faut faire repasser la réservation à
l'état « terminée » afin d'y ajouter les produits et services manquants.

**Sur la fiche de la réservation :** repasser à l'état « terminée » (via
`ACTIONS > REPASSER EN TERMINÉE`)

Si tout est bien correct, la facture finale peut alors être émise.

**Sur la fiche de la facture proforma** : émettre la facture (via
`ACTIONS > EMETTRE LA FACTURE`)

Dans le cas où une facture a été émise mais qu'on se rend compte que le
montant est significativement erroné et qu'il faut adapter le montant
facturé, il y a deux options :

1. soit on annule la facture en émettant une note de crédit
   (attention, dans ce cas, il y aura 3 pièces comptables : la facture
   originale, la note de crédit correspondante, et la nouvelle facture)

2. soit on crée une nouvelle facture en créant une nouvelle
   réservation (fictive) dans laquelle on ne met que les suppléments à
   facturer au client concerné.

Une fois émise, une facture peut être consultée, imprimée et envoyée.

**Sur la fiche de la facture :** Dans le panneau latéral droit, utiliser
le bouton « Imprimer/Envoyer » pour voir, imprimer ou envoyer la
facture.
