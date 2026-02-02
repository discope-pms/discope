# Calendrier des Animateurs

Le calendrier des animateurs présente l'ensemble des activités liées aux réservations ou aux camps.

- Axe vertical : liste des animateurs et prestataires auxquels les activités peuvent être liées.
- Axe horizontal : les dates et tranches horaires (matin, midi, soir) correspondant aux moments des activités.

Le calendrier permet d'assigner des activités aux animateurs et prestataires, les activités pas encore assignées sont présentées sous le calendrier.

---

## Où le trouver ?

Apps dashboard → Réservations → Calendrier Animateurs

Astuce : Le Calendrier Animateurs se trouve dans le menu du haut.

---

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

---

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

---

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

