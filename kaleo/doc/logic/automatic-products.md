Dans certaines conditions, des produits sont ajoutés automatiquement à
une réservation, sur base de critères prédéfinis. Ces « produits
automatiques » ou « produits systématiques » s'appliquent au niveau de
la réservation (groupes de services supplémentaires), contrairement aux
réductions qui s'appliquent au niveau de leur ligne correspondante (au
sein d'un groupe).

Il est possible de définir une liste de produits automatiquement ajoutés
à chaque réservation, éventuellement en fonction de critères spécifiques : 
nombre de personnes et dates définies dans la composition.

Les conditions d'application des produits systématiques sont définies
de la même manière que pour les réductions (opérande, opérateur, valeur)
avec des conditions relatives à la réservation en cours ([nouveau
client, liste des catégories des produits repris dans la réservation ;
...])

Dans tous les cas, il est possible de modifier manuellement la liste des
produits de la réservation : soit en en ajoutant, soit en retirant des
produits ajoutés automatiquement.

Il est possible d'ajouter des produits à une réservation :

-   Pour les produits automatiques
-   Pour les consommations, lorsque la réservation a été finalisée
    (contrat envoyé)

=> "prestations supplémentaires" (généralement comptabilisées à
l'unité)

## Scope d'application des produits automatiques

A la création d'un produit automatique, il faut préciser le scope de son
application : 'booking' ou 'group'

Les produits auto de type 'booking' s'appliquent sur toute la
réservation :

-   Les produits auto sont ajoutés dans des groupes à part qui leur sont
    propres (a une propriété is_extra à true),
-   Mise à jour à chaque modification de : `customer_id`

Les produits auto de type 'group' s'appliquent uniquement sur un
groupe de service :

-   Les opérandes sont : `nb_pers`, `nb_nights`
-   Produits auto sont ajoutés dans les groupes sur lesquels ils
    s'appliquent
-   Mise à jour à chaque modification de : `nb_nights`, `nb_pers`

Notes :

-   Lorsqu'on ajoute un produit automatique, il faut s'assurer qu'il
    n'a pas déjà été ajouté : les produits auto sont associés à un SKU
    -   Si aucun => créer un nouveau groupe 'suppléments'
-   Les produits automatiques sont identifiables par le champ `is_auto`
    (au niveau des booking_lines) qui est marqué à True.
-   Les produits automatiques doivent être inclus dans le contrat
    (marqués is_contractual lors de la validation)
-   Les consommations supplémentaires, par contre, ne sont pas marquées
    comme is_invoiced. Si elles ont été payées en caisse, le
    payment_mode est ajusté : invoice, cash, free) (Si elles sont
    marquées avec payment_mode = 'invoice' elles sont traitées au
    moment où la réservation est soldée).
