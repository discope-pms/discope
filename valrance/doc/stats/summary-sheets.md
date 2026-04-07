# Fiches récapitulatives

Le document Excel des fiches récapitulatives est utilisé par le Relais Valrance pour générer un livret récapitulatif, notamment via un publipostage Word.

## Où le trouver

`Apps dashboard -> Statistiques (Valrance) -> Stats Réservation -> Fiches récap.`

## Finalité

Cette vue rassemble, réservation par réservation, toutes les informations utiles à la production d'une fiche de synthèse :

- identification de la réservation ;
- coordonnées client ;
- contacts ;
- repas du premier et du dernier jour ;
- répartition des participants ;
- informations de déplacement ;
- unités locatives affectées.

## Filtres disponibles

La recherche s'appuie sur un filtre simple :

- date de début ;
- date de fin.

## Données restituées

Le provider produit notamment les champs suivants :

- nom de réservation ;
- nom client ;
- compte comptable ;
- dates formatées ;
- catégorie tarifaire ;
- statut ;
- adresse et contacts ;
- premier repas ;
- dernier repas ;
- nombre d'enfants, d'enseignants, d'adultes et de chauffeurs ;
- indicateur maternelle ;
- déplacements ;
- unités locatives.

## Règle "Repas 1er jour"

La description est calculée en fonction des repas fournis par Valrance le premier jour :

- petit-déjeuner ;
- déjeuner ;
- goûter ;
- dîner ;
- ou, à défaut, la nuitée.

Le cas du pique-nique de midi ajoute une seconde partie textuelle selon que le pique-nique et le goûter sont fournis ou non par Valrance.

## Règle "Repas dernier jour"

La description du dernier jour combine :

- le dernier repas pris sur place ;
- d'éventuels repas ou collations à emporter.

Le calcul dépend à la fois :

- de la présence du repas ;
- du fait qu'il soit fourni par Valrance ;
- du fait qu'il soit consommé sur place ou en extérieur.

## Déplacements

La colonne déplacements est construite à partir de produits de réservation et de la composition du groupe.

Les cas explicitement gérés dans le code sont :

- `A/R avec les Voyages MASSOL`
- `Le bus reste sur place`
- `Déplacement avec les Voyages MASSOL`
- `Déplacement avec VERBUS`

## Répartition des participants

Le calcul distingue explicitement :

- enfants ;
- enseignants ;
- adultes ;
- chauffeurs.

Le code s'appuie sur les tranches d'âges associées aux groupes de séjour.

## Indicateur maternelle

La valeur `kindergarten` est alimentée à partir d'un attribut de groupe portant le code `kindergarten`.

## Remarque de couverture

Dans le code, un commentaire signale encore certains besoins documentaires ou fonctionnels autour des fiches récapitulatives. La page ci-dessus décrit donc le comportement effectivement implémenté, tout en indiquant qu'il peut encore évoluer.
