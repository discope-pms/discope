# Programme de la semaine

Le programme de la semaine des réservations liste les réservations de la semaine en cours.

Informations :
- Groupes (Nom du client de la réservation)
- Dates (Date de début et fin de la réservation)
- Nombre (Nombre d'enfants + adultes participants)
- Age (Tranche d'âge des enfants s'il y en a, sinon tranche d'âge des adultes)
- Planning (Des activités sont planifiées durant le séjour)
- Frigo (Mise à disposition d'un frigo)
- Répartition (Unités locatives assignées)
- Handicap (Une personne avec un handicap participe)
- Repas (Des repas sont planifiés durant le séjour)

## Où le trouver ?

Apps dashboard → Réservations → Planning → En cours

## Paramètres

1) SKUs mise à disposition frigo
- Liste des SKUs des produits de mise à disposition d'un frigo
- Clé: `sale.booking.icebox_skus`

## Usage opérationnel

Cette vue sert de synthèse hebdomadaire pour les équipes d'accueil et de coordination.

Elle met en évidence des signaux utiles sans ouvrir chaque réservation :

- présence d'activités planifiées ;
- besoin de frigo ;
- répartition dans les unités locatives ;
- présence d'un participant avec handicap ;
- repas planifiés.

## Lecture PO

Le programme de la semaine n'est pas un document contractuel. C'est une vue de préparation et de coordination, orientée exécution.

## Lecture DEV

La présence de certains indicateurs dépend du paramétrage ou du contenu des lignes de réservation, notamment le cas du frigo via la clé de configuration `sale.booking.icebox_skus`.
