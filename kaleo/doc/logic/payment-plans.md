Dans Discope, les acomptes font partie du système de "financements"
(ou "versements anticipés"). Les plans de financement permettent de
planifier une ou plusieurs dates pour lesquelles un montant est attendu
de la part du client pour confirmer sa réservation.

Un financement représente un pourcentage du montant repris sur le
contrat (qui n'est pas nécessairement le montant final étant donné que
le contrat peut encore être modifié et que des prestations peuvent avoir
lieu pendant ou après le séjour).

Les demandes d'acompte (financements) sont générées automatiquement (et
peuvent être complétées manuellement). Pour déterminer les dates
auxquelles des paiements sont dus, on utilise des plans de paiement (qui
fonctionnent comme des templates d'échéanciers) qui génèrent
automatiquement des financements (demandes de paiement faites au
client).

## Modification du plan de paiement

Lorsque les services réservés d'une réservation ont été modifiés
(passant de réservation à devis) ou lors de la confirmation de la
réservation :

**De manière général :**

-   Tout financement impayé existant avant la confirmation est supprimé.

-   Cependant, ceux qui sont payés ou partiellement payés sont
    conservés.

Si un nouveau contrat est ajouté avant la date limite, il y aura un seul
financement "Full" avec la différence entre le prix total déduit des
paiements déjà reçus.

Dans le cas contraire, un nouveau plan de paiement sera établi en
fonction du solde de la réservation, en prenant en compte les
financements conservés.

Dans le cas où la somme totale des financements payés dépasse le montant
total de la réservation, aucun nouveau plan de paiement ne sera créé.

**Au moment de l'émission de la facturation de solde d'une réservation :**

-   S'il y a des factures proforma (solde ou acompte), elles sont
    supprimées

-   S'il y a des financements (totalement) non payés non rattachés à
    une facture, ils sont supprimés

-   S'il y a des financements partiellement payés, ils sont conservés
    (le montant dû est adapté pour correspondre au montant reçu)

-   Les financements rattachés à une facture (d'acompte) qui n'aurait
    pas été émise sont convertis en prépaiements

-   Les financements rattachés à une facture (d'acompte) émise sont
    conservés, même s'ils ne sont pas totalement payés : on doit
    continuer à faire le suivi des montants attendus et reçus

-   Une nouvelle facture de solde proforma est générée qui reprend tous
    les paiements déjà reçus. Dans le cas de financements partiellement
    payés, c'est le montant effectivement payé qui est déduit de la
    facture.
