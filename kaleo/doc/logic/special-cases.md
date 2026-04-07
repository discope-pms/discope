# Cas particuliers

## Cas particulier : VSG - La ferme

Le centre VSG presente une logique specifique pour l'assignation des prix sur certaines offres liees a "La Ferme".

Pour les offres concernees, les tarifs appliques doivent provenir directement du catalogue et de la liste de prix applicable, sans application des avantages lies a la categorie tarifaire ni des adaptations de prix propres a la reservation.

La mise en oeuvre repose sur des packs dedies dont l'option `prix adaptables` est desactivee.

Consequences metier :

- les avantages tarifaires ne sont pas appliques aux produits du pack;
- les produits ajoutes manuellement dans ce pack restent eux aussi exclus des adaptations de prix;
- un pack technique vide `Pack prix bruts (VSG)` permet d'ajouter manuellement des repas ou produits connexes tout en conservant une logique de prix bruts.

## Sejours scolaires / CDV

La reference CDV correspond a une offre "classes decouvertes" destinee principalement aux ecoles.

Les principes metier repris dans le document source sont les suivants :

- l'offre met en avant un tarif de moyenne saison integrant les reductions liees a la categorie tarifaire ecole;
- en gites de groupes, les reductions ne s'appliquent pas toujours de la meme maniere que pour les auberges, notamment lorsqu'il y a des repas;
- les sejours scolaires comportent souvent des produits supplementaires a gerer en plus des nuitees et repas.

## Logique d'acompte pour les sejours scolaires

Le fonctionnement decrit prevoit un acompte maximal de 85 %, decoupe comme suit :

1. 10 % a payer rapidement apres emission du contrat pour securiser la confirmation.
2. 75 % a payer deux mois avant le sejour.

Le reste du flux s'organise ainsi :

- rappel avant sejour pour valider les presences;
- ajustements eventuels en cours de sejour;
- reductions de fin de sejour si des participants sont absents;
- generation d'une proforma puis d'une facture de solde.

## Exception sur la taxe de sejour

Lorsque le groupe de services correspond a un sejour lie a un pack dont le modele de produit est rattache au type de reservation `sejour scolaire`, la taxe de sejour automatique au niveau du groupe n'est pas ajoutee.

Le document source precise qu'il s'agit d'une exception metier specifique a cette situation.
