## PWA Planning Animateurs

# 1. Application

L'App **PWA Planning Animateurs** est une interface mobile qui sert à la **consultation et la manipulation opérationnelle du planning animateurs**.

* Application **mobile-first**
* **Distincte de l’interface desktop**
* **Connectée en temps réel à l’API Discope**
* **Sans persistance locale ni mode offline**

Elle ne constitue pas un système autonome :
→ **toute la logique métier est portée par l’API**

---

# 2. Modèle fonctionnel

## 2.1 Acteurs

| Acteur       | Rôle fonctionnel                                        |
| ------------ | ------------------------------------------------------- |
| Animateur    | Consultation uniquement                                 |
| Manager      | Gestion opérationnelle (assignations, indisponibilités) |
| Organisateur | Supervision globale                                     |

Les droits sont déterminés par les **userGroups**, avec priorité au plus permissif.

---

## 2.2 Concepts métier

### EmployeeRole

Rôle métier d’un animateur.

* Cardinalité : **1 employé → 1 rôle**
* Usage :

  * Filtrage du planning
  * Détermination du périmètre de visibilité

⚠️ Important :

* Les `EmployeeRole` sont **souvent configurés avec des libellés similaires aux catégories d’activités**
* **Aucun lien structurel n’existe** entre rôle et catégorie
* Toute correspondance est **conventionnelle côté client**, pas garantie par le modèle

---

### UserGroup

Rôle applicatif (permissions).

* Cardinalité : **N groupes par utilisateur**
* Règle :

  * **union des droits**
  * priorité au plus permissif

---

### Activity (assignable)

Activité planifiée.

Cas particulier :

* **activité sans animateur → “activité à assigner”**

Ce n’est **ni** :

* un créneau vide
* un événement libre (PartnerEvent)

---

### ProductModel (structurant)

Les activités sont liées à un `product_model_id`.

```text
BookingActivity → product_model_id
```

Ce modèle produit constitue la base de structuration des activités :

* typologie
* regroupements implicites
* filtrage

⚠️ Important :

* Il n’existe **pas de relation directe avec une entité Category métier**
* La notion de "catégorie" est **dérivée indirectement du catalogue produit**

---

### Category (notion dérivée)

La notion de catégorie :

* n’est pas portée par une entité métier explicite
* est reconstruite à partir des `product_model_ids`

👉 Concrètement :

```text
Category ≈ regroupement de product_model_ids
```

Conséquences :

* dépend du **jeu de données chargé**
* non stable si chargement partiel
* non exploitable de manière uniforme selon les rôles

---

### PartnerEvent

Objet générique représentant :

* indisponibilité
* blocage
* note interne
* événement administratif

→ **aucun autre système d’absence n’existe**

---

# 3. Structure du planning

## 3.1 Modèle de grille

Le planning est une grille :

* **Lignes** : animateurs
* **Colonnes** : moments fixes

Moments définis globalement :

* MAT.
* APR.
* SOIR

---

## 3.2 Définition d’un “moment”

Un moment est une clé unique :

```
(employee, date, timeslot)
```

Contraintes :

* **1 seule assignation par moment**
* Pas de multi-affectation

---

## 3.3 Affichage

* Vue glissante (jour/semaine)
* Nombre de jours dépend de la largeur écran
* Navigation :

  * swipe horizontal
  * boutons
  * sélection calendrier

---

# 4. Gestion des assignations

## 4.1 Source des assignations

Deux sources :

1. Activités déjà assignées
2. Activités à assigner (pool)

---

## 4.2 Interaction principale

### Drag & Drop

Cas autorisés :

* panneau → cellule
* cellule → cellule

Contraintes :

* drop uniquement sur zone visible
* pas d’auto-scroll
* validation **uniquement via API**

---

## 4.3 Validation

* aucune mutation locale persistée
* chaque action → appel API

En cas d’erreur :

* rollback implicite (aucun état local)
* affichage d’un message (snack)

---

## 4.4 Suppressions

| Action                 | Manager | Organisateur |
| ---------------------- | ------- | ------------ |
| Supprimer assignation  | ✔       | ✔            |
| Supprimer PartnerEvent | ✖       | ✔            |
| Supprimer activité     | ✖       | ✖            |

---

# 5. Consultation d’un moment

Interaction :

* clic → ouverture dialog fullscreen

Contenu (lecture seule) :

* réservation
* groupe
* numéro
* date
* tranche (MAT/APR/SOIR)
* heures précises
* type d’activité (product model)

→ **aucune édition dans l’application**

---

# 6. Filtres

## 6.1 Animateurs

* par `employeeRole`
* par nom

---

## 6.2 Activités

* toutes
* par modèle produit (`product_model_id`)

---

## 6.3 Catégories (comportement spécifique)

⚠️ Le filtre "catégorie" n’est **pas basé sur une entité Category explicite**.

Il repose sur :

```text
un sous-ensemble de product_model_ids
```

### Cas Organisateur (Admin)

* Chargement basé sur le **catalogue produit**
* Vision complète des `product_model_ids`
* Possibilité de reconstruire un filtre catégorie global

👉 Le filtre catégorie est ici :

* **catalogue-driven**
* stable et cohérent

---

### Cas Manager / Animateur

* Chargement basé sur les **activités des employés du groupe**
* Sous-ensemble dynamique de `product_model_ids`
* Mélange possible de catégories

👉 Le filtre catégorie catalogue :

* **non reconstructible correctement**
* donc absent dans l’état actuel

---

### Conséquence

Il existe en réalité deux logiques de filtrage :

```text
1. Filtre catégorie (catalogue)
2. Filtre catégorie (activités chargées)
```

Ces deux filtres :

* reposent sur des sources différentes
* ne sont pas compatibles
* doivent être considérés comme distincts

---

### Évolution possible

Pour les managers / animateurs :

* ajout d’un filtre catégorie basé sur les **activités visibles**
* appliqué en surcouche
* mutuellement exclusif avec le filtre catalogue

---

## 6.4 Scope par défaut

| Groupe       | Scope   |
| ------------ | ------- |
| Animateur    | secteur |
| Manager      | secteur |
| Organisateur | global  |

---

# 7. Synchronisation et concurrence

## 7.1 Modèle

* **temps réel**
* **stateless côté client**
* aucune cache métier

---

## 7.2 Conflits

* aucune gestion de verrou
* stratégie : **last write wins**

---

## 7.3 Rafraîchissement

* pas d’auto-refresh
* refresh manuel uniquement

---

# 8. Offline

Non supporté :

* aucune donnée stockée
* aucune action différée
* connexion obligatoire

---

# 9. Sécurité

* authentification via session
* permissions validées côté API
* front-end non autoritaire

---

# 10. Architecture technique

## 10.1 Stack

* Angular
* Angular Material
* CSS Grid / Flexbox
* PWA

---

## 10.2 Layout

* grille dynamique (pas de `<table>`)
* responsive mobile-first

---

## 10.3 Gestion des gestes

Implémentation native :

* `touchstart`
* `touchmove`
* `touchend`

Conditions :

* détection horizontale : `|dx| > |dy|`
* seuil minimal
* `preventDefault` uniquement si swipe confirmé

CSS :

```
touch-action: pan-y;
```

---

## 10.4 Chargement des données

Deux stratégies distinctes :

### Mode Organisateur

```text
Chargement via product_model
→ catalogue complet
→ structuration globale
```

### Mode Manager / Animateur

```text
Chargement via activités des employés
→ sous-ensemble dynamique
→ dépend des assignations
```

Conséquence :

* les données ne sont **pas homogènes**
* certaines fonctionnalités (ex : catégories) ne sont **pas universellement applicables**

---

# 11. Règles structurantes

## 11.1 Invariants métier

* 1 animateur = 1 rôle métier
* 1 moment = 1 assignation max
* absence = PartnerEvent
* aucune modification locale persistée

---

## 11.2 Responsabilités

| Front (PWA)         | API                 |
| ------------------- | ------------------- |
| UI / interactions   | logique métier      |
| validation minimale | validation complète |
| affichage           | source de vérité    |

---

# 12. Hors scope explicite

* mode offline
* historique dans l’app
* verrou concurrentiel
* suppression d’activité
* gestion avancée des conflits
* auto-refresh
* système d’absences dédié

