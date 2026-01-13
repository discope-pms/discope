# Doc Valrance

## Reservation

### Programme par groupe

Le programme par groupe d'une réservation génère un document PDF qui affiche les plannings des activités des chaque groupe avec détails.

#### 📍 Où le trouver ?

`Apps dashboard → Réservations → Fiche réservation -> Programme par groupe`

> 💡 **Astuce :** Le *Programme par groupe* se trouve dans le **menu de droite**.

#### Commentaires premier et dernier jour

Il est possible d'ajouter des commentaires à certain moment d'une journée est utilisant le template modèle `RV.planning.activity.doc` :
  - Commentaire premier jour au matin : `first_day_am_comment`
  - Commentaire premier jour après-midi : `first_day_pm_comment`
  - Commentaire premier jour au soir : `first_day_ev_comment`
  - Commentaire dernier jour au matin : `last_day_am_comment`
  - Commentaire dernier jour après-midi : `last_day_pm_comment`
  - Commentaire dernier jour au soir : `last_day_ev_comment`

Par exemple un commentaire le dernier jour au matin peut-être ajouté pour dire **Ranger et libérer les chambres avant 9h00**.

## Stats

### Fiches récapitulatives

Le document Excel des fiches récapitulatives est utilisé, par le Relais Valrance, pour générer le livret récapitulatif.
Ils utilisent un document Word en publipostage, afin de créer ce livret.

#### 📍 Où le trouver ?

`Apps dashboard → Statistiques (Valrance) → Stats Réservation → Fiches récap.`

#### Note : "Repas 1er jour"

_1ère partie_ :

| Valeur                 | Conditions                                                                    |
|------------------------|-------------------------------------------------------------------------------|
| pour le petit-déjeuner | Si le petit-déjeuner est fourni par Valrance.                                 |
| pour le déjeuner       | Si le déjeuner est fourni par Valrance, mais pas le petit-déjeuner.           |
| pour le goûter         | Si le seul repas fourni par Valrance est le goûter.                           |
| pour le dîner          | Si le dîner est fourni par Valrance, mais pas le petit-déjeuner, ni déjeuner. |
| pour la nuitée         | Si aucun repas n'est fourni par Valrance.                                     |

_2ème partie_ (si le déjeuner est un picnic) :

| Valeur                                                                        | Conditions                                                                                                             |
|-------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------|
| avec pique-nique et goûter fournis par le Relais Valrance                     | Si le déjeuner est un picnic et que le déjeuner et le goûter sont fournis par Valrance.                                |
| avec pique-nique fourni par le Relais Valrance                                | Si le déjeuner est un picnic et que le déjeuner est fourni par Valrance, mais pas le goûter.                           |
| avec pique-nique amenés par vos soins et goûter fourni par le Relais Valrance | Si le déjeuner est un picnic et que le déjeuner n'est pas fourni par Valrance, mais le goûter est fourni par Valrance. |
| avec pique-nique et goûter amenés par vos soins                               | Si le déjeuner est un picnic et que ni le déjeuner ou le goûter sont fournis par Valrance.                             |

Format : {_1èr partie_}, {_2ème partie_}

Note format : la 2ème partie est optionnel

Exemples : 

 - pour le déjeuner
 - pour le dîner
 - pour le dîner, avec pique-nique et goûter amenés par vos soins
 - ...

#### Note : "Repas dernier jour"

_1ère partie_ :

| Valeur                  | Conditions                                                                                                                                         |
|-------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------|
| après le dîner          | Si le dîner est fourni par Valrance et n'est pas pris en extérieur.                                                                                |
| après le goûter         | Si le dîner n'est pas fourni par Valrance, mais que le goûter est fourni et qu'il n'est pas pris en extérieur.                                     |
| après le déjeuner       | Si le dîner et le goûter ne sont pas fourni par Valrance, mais que le déjeuner est fourni et qu'il n'est pas pris en extérieur.                    |
| après le petit-déjeuner | Si le dîner, le goûter et le déjeuner ne sont pas fourni par Valrance, mais que le petit-déjeuner est fourni et qu'il n'est pas pris en extérieur. |

_2ème partie_ :

| Valeur                                                                                | Conditions                                                                                                                                |
|---------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------|
| avec collation petit-déjeuner, pique-nique, goûter, et pique-nique du soir à emporter | Si le petit-déjeuner est fourni par Valrance et que tous les repas/snack sont pris en extérieur.                                          |
| avec collation petit-déjeuner, pique-nique et goûter à emporter                       | Si le petit-déjeuner est fourni par Valrance et que tous les repas/snack sont pris en extérieur, sauf le dîner.                           |
| avec collation petit-déjeuner et pique-nique à emporter                               | Si le petit-déjeuner est fourni par Valrance et que le petit-déjeuner et le déjeuner sont pris en extérieur, mais pas le goûter et dîner. |
| avec collation petit-déjeuner à emporter                                              | Si le petit-déjeuner est fourni par Valrance et que le petit-déjeuner est pris en extérieur, mais pas le déjeuner, le goûter et dîner.    |
| avec pique-nique, goûter, et pique-nique du soir à emporter                           |                                                                                                                                           |
| avec pique-nique et goûter à emporter                                                 |                                                                                                                                           |
| avec pique-nique à emporter                                                           |                                                                                                                                           |
| avec goûter et pique-nique du soir à emporter                                         |                                                                                                                                           |
| avec goûter à emporter                                                                |                                                                                                                                           |
| avec pique-nique du soir à emporter                                                   |                                                                                                                                           |

#### Note : "Deplacements"


