# Calendrier des Animateurs

Le calendrier des animateurs présente l'ensemble des activités liées aux réservations ou aux camps.

- Axe vertical : liste des animateurs et prestataires auxquels les activités peuvent être liées.
- Axe horizontal : les dates et tranches horaires (matin, midi, soir) correspondant aux moments des activités.

Le calendrier permet d'assigner des activités aux animateurs et prestataires, les activités pas encore assignées sont présentées sous le calendrier.


## Accès

Apps dashboard → Réservations → Calendrier Animateurs

Astuce : Le Calendrier Animateurs se trouve dans le menu du haut.



## Activités

Une activité peut être liée à une réservation ou un camp ou indépendante.

Règles :
- Si le champ Nécessite du personnel (`has_staff_required`) est activé → l'activité doit être assignée à un animateur.
- Si le champ Exclusive (`is_exclusive`) est activé → l'activité ne peut pas partager la même tranche horaire avec une autre activité pour un même animateur.

Création/Configuration :
- Réservation : Depuis une fiche d'une réservation (Menu droite) Planning activités
- Camp : Depuis une fiche d'un groupe de camp (Onglet) Activités
- Indépendante : Calendrier Animateurs (Bouton en bas à gauche) Créer activités (Série d'activités)

Suppression :
- Les activités indépendantes peuvent être supprimées depuis le Calendrier Animateurs.
- Les activités réservation/camp doivent être supprimées depuis les fiches réservation/camp, pas dans le Calendrier Animateurs.

⚡ Les séries d'activités permettent la création rapide d'activités indépendantes récurrentes entre deux dates.


## Événements

Les événements ajoutent des informations spécifiques à un moment donné pour un animateur (ex. congé, repos…). Ils peuvent également représenter un groupe de camp dont un animateur est responsable.

Types d'événements :
- Info animateur : Congé, Autre, Repos, Récupération, Formateur, Formation
- Camp : Activité d'un camp (l'animateur est responsable d'un groupe de camps durant la tranche horaire)

Les événements ne peuvent pas être déplacés par drag and drop.

Ils sont modifiables depuis la fiche événement (accessible par clic sur l'événement).

Création :
- Double-clic sur une case du calendrier.
- Depuis une fiche animateur/prestataire (Onglet) Événements ou (Onglet) Séries d'événements.

⚡ Les séries d'événements permettent la création rapide d'événements récurrents entre deux dates.


## Paramètres

Deux paramètres permettent de configurer le calendrier :

1) Activer le Calendrier Animateurs
- Active ou désactive l'affichage du calendrier.
- Clé: `sale.features.booking.employee_planning`

2) Filtrer les activités assignables
- Restreint l'assignation des activités aux animateurs et prestataires habilités à les accepter.
- Configuration :
  - Animateurs (employés) → Fiche employé → (Onglet) Modèle de produits
  - Prestataires → Fiche prestataire → (Onglet) Modèle de produits
- Clé: `sale.features.employee.activity_filter`




## Multi-assignation des activités aux animateurs (Planning)

Cette évolution introduit la possibilité d’assigner plusieurs activités (groupes / séjours) à un même animateur sur une même plage horaire.

Jusqu’à présent, le modèle reposait sur une relation 1-1 (une activité ↔ un animateur par créneau). Cette contrainte est levée pour permettre une plus grande flexibilité dans la planification, notamment pour des cas métiers comme l’équitation (sessions consécutives) ou des activités parallèles.

Cette évolution implique un passage à une relation de type N-N entre animateurs et activités, avec des impacts potentiellement larges sur le modèle et les comportements existants. Le périmètre implémenté ici correspond volontairement à un compromis maîtrisé pour le MVP.



### Logique métier

Un animateur peut désormais être assigné à plusieurs activités sur un même créneau (matin / après-midi / soir).

Deux modes de fonctionnement coexistent :

- un mode **structuré**, basé sur le créneau, avec recalcul automatique des horaires
- un mode **manuel**, où les horaires d’une activité sont définis explicitement

Le système ne cherche pas à résoudre tous les cas complexes automatiquement. Une partie de la responsabilité est laissée à l’utilisateur afin de garantir un comportement simple, lisible et prédictible.



### Notion d’activité “automatique” vs “manuelle”

Une activité est considérée comme **automatique** si ses horaires correspondent exactement à ceux du créneau.

Une activité est considérée comme **manuelle** si ses horaires ont été modifiés.

Règle :

```
manual = (time_from / time_to != boundaries du time_slot)
```

Cette distinction n’est pas portée par un champ explicite, mais déduite dynamiquement.



### Comportement du Drag & Drop

Le positionnement lors du drag & drop détermine l’intention de l’utilisateur :

- **gauche / droite** → placement dans la continuité (logique consécutive)
- **centre** → placement en parallèle (logique superposée)

Cette intention est prioritaire dans la décision de recalcul.



### Règles de planification

#### Cas 1 — Placement en parallèle (centre)

Aucune modification des horaires n’est effectuée.

L’activité conserve ses horaires existants, qu’ils soient issus du créneau ou définis manuellement.

Les autres activités du créneau ne sont pas modifiées.

Ce mode permet d’avoir plusieurs activités simultanées pour un même animateur.



#### Cas 2 — Placement en continuité (gauche / droite)

Le comportement dépend du nombre d’activités présentes après assignation.

##### Une seule activité

Si l’activité est seule dans le créneau :

- si elle est automatique → elle prend les horaires du créneau
- si elle est manuelle → ses horaires sont conservés

##### Plusieurs activités

Dès qu’il y a plusieurs activités sur un même créneau :

- les horaires sont recalculés automatiquement pour toutes les activités
- le créneau est découpé en intervalles égaux (`splitSchedule`)
- chaque activité reçoit un intervalle

Important :

> Si des activités avaient des horaires définis manuellement, ceux-ci sont écrasés lors du recalcul.

Autrement dit, le mode manuel n’est pas persistant dès qu’on entre dans une logique multi-activités.



### Priorité des règles

L’ordre de décision est le suivant :

1. Le positionnement (centre vs gauche/droite) détermine le mode (parallèle vs consécutif)
2. Si placement en parallèle → aucune modification des horaires
3. Si placement en continuité :
   - une seule activité → manuel possible
   - plusieurs activités → recalcul automatique global



### Règle spécifique : activités exclusives

Certaines activités peuvent être marquées comme exclusives (`is_exclusive = true`).

Dans ce cas, une seule activité peut être assignée à un animateur pour un créneau donné, quelle que soit l’intention de placement.

Cette contrainte est gérée en amont et n’est pas traitée dans la logique de planification décrite ici.



### Limites

Le système ne gère volontairement pas les cas suivants :

- coexistence de plusieurs activités avec horaires manuels sans recalcul
- détection automatique de conflits ou d’incohérences
- adaptation intelligente des horaires en fonction du contexte

Ces cas nécessiteraient un modèle de planning libre basé sur des horaires indépendants, ce qui sort du cadre du MVP.



