Le planning est commun à tous les centres (le rôle Admin peut tout
voir).

Au niveau du récapitulatif final, il faut pouvoir reprendre les repas
servis (regroupés par jour, quel que soit le type de repas et le produit
dont ils découlent).

### Accès aux factures depuis le planning

Depuis le planning, la popup de détail d'une réservation permet de
consulter les factures liées à cette réservation via
`Réservation > Factures > Facture`.

Le lien d'une facture ouvre la fiche de la facture dans un nouvel onglet
en conservant le contexte de la réservation. L'adresse doit donc contenir
à la fois l'identifiant de la réservation et l'identifiant de la facture :

`/booking/#/booking/{booking_id}/invoice/{invoice_id}`

Par exemple, pour la réservation `12345` et la facture `35976`, l'adresse
attendue est :

`/booking/#/booking/12345/invoice/35976`

Ce contexte est nécessaire pour que le composant de facture puisse être
initialisé correctement et pour que les actions disponibles sur la fiche
de facture, dont l'impression et l'envoi, fonctionnent. L'adresse ne doit
pas contenir de valeur technique non interpolée comme `object.booking_id`.

### Filtres d'affichage des unités locatives

Dans l'en-tête du planning, une icône « settings » permet d'ouvrir un
dialogue de préférences du filtre d'affichage des unités locatives. Des
options permettent de choisir quelles unités doivent être visibles dans
le planning.

### Liste d'occupations arrivées et départs par jour

Dans le bas du planning, une série de données sont présentées par jour.

-   Capacité : La capacité correspond à la capacité théorique du nombre
    d'unités locatives, déduit des unités marquées comme bloquées. Note : 
    les blocages se font par journée complète et ont donc un impact à
    partir de la veille du premier jour du blocage, jusqu'au dernier
    jour du blocage.

-   Bloquées : Ce nombre reprend la somme des unités locatives qui sont
    bloquées à une date donnée.

-   Occupées : Ce nombre reprend la somme des unités locatives qui sont
    occupées par des clients à une date donnée (hors blocages).

-   Taux d'occupation : Le taux d'occupation correspond, pour chaque
    date, au rapport entre le nombre d'unités occupées sur le nombre
    d'unités disponibles (nombre total d'unités, déduit des unités
    bloquées).

-   Arrivées : Ce nombre reprend le nombre de séjours, pour une date
    donnée, dont la première date (date d'arrivée) correspond à la
    date. Lorsque la date correspond à la date du jour, l'information
    est divisée en deux partie : le nombre de réservations pour lesquels
    le checkin a déjà été fait, et le nombre total d'arrivées prévues.

-   Départs : Ce nombre reprend le nombre de séjours, pour une date
    donnée, dont la dernière date (date de départ) correspond à la date.
    Lorsque la date correspond à la date du jour, l'information est
    divisée en deux partie : le nombre de réservations pour lesquelles
    les clients sont déjà partis, et le nombre total de départs prévus.

Notes : Seules les unités locatives affichées sont prises en
considération (il y a donc un impact selon que l'utilisateur choisit
d'afficher ou non les unités parentes ou les unités enfants).

Les informations renseignées ne donnent pas le détail de la capacité en
termes de lits, mais en termes d'unité locative (une chambre privative
de 4 personnes dans laquelle logent uniquement 2 personnes, sera
considérée comme entièrement occupée).

Les données sont rafraîchies automatiquement toutes les 5 minutes.
