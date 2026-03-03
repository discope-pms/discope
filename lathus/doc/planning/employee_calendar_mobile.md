Parfait 👍 tu as raison.

Ce que tu m’as donné, ce sont des **spécifications fonctionnelles et techniques**.
Voici maintenant une **page de documentation orientée utilisateur / produit**, expliquant :

* ce que c’est
* à quoi ça sert
* comment ça fonctionne
* sans ton technique trop “spec”

---

# 📱 PWA Planning Animateurs – CPA Lathus

## Présentation

La **PWA Planning Animateurs** est une application mobile spécifique développée pour le **CPA Lathus**.

Elle s’intègre directement à **Discope** et propose une interface mobile dédiée à la gestion du planning des animateurs.

Contrairement à l’interface web classique de Discope (orientée desktop), cette application est conçue exclusivement pour un usage sur smartphone.

Elle permet aux équipes terrain de :

* Consulter le planning
* Assigner des activités
* Gérer les indisponibilités
* Réorganiser les affectations rapidement

Le tout en temps réel, connecté directement à l’API Discope.

---

# 🎯 À quoi sert l’application ?

L’application répond à un besoin simple :

> Permettre aux animateurs et responsables de gérer le planning depuis le terrain, rapidement et sans passer par l’interface desktop.

Elle est utilisée par :

* **Les animateurs** (consultation)
* **Les managers de secteur** (gestion opérationnelle)
* **Les organisateurs** (vision globale et coordination)

---

# 🗂 Comment est structuré le planning ?

Le planning est organisé sous forme de tableau :

* Chaque ligne représente un **animateur**
* Chaque colonne correspond à un **moment de la journée**

Les moments sont fixes :

* MAT. (matin)
* APR. (après-midi)
* SOIR

Un animateur ne peut avoir qu’une seule activité par moment.

L’affichage peut couvrir :

* Un seul jour
* Plusieurs jours selon la largeur de l’écran

La navigation se fait simplement :

* Par swipe horizontal
* Ou via les boutons de navigation
* Avec possibilité de sélectionner une date via un calendrier

---

# 👥 Gestion des rôles

## Rôle métier (Employee Role)

Chaque animateur possède un **rôle métier** (ex : Équitation, Environnement, Sport & Cirque, Camps).

Ce rôle permet :

* De filtrer l’affichage
* De déterminer les activités compatibles

Un animateur ne peut avoir qu’un seul rôle métier.

---

## Rôles applicatifs (Groupes utilisateurs)

Les droits dans l’application dépendent du groupe utilisateur :

### Animateur

* Consultation uniquement

### Manager

* Réassigner un animateur
* Supprimer une assignation
* Créer ou modifier une indisponibilité

### Organisateur

* Accès complet
* Gestion globale du planning

Si un utilisateur appartient à plusieurs groupes, le niveau de droit le plus élevé s’applique.

---

# ➕ Activités à assigner

Certaines activités sont planifiées sans animateur.

Elles apparaissent dans un panneau spécifique :
**“Activités à assigner”**

Depuis ce panneau, un manager ou organisateur peut :

* Glisser-déposer l’activité vers un animateur
* La repositionner si nécessaire

Chaque modification est validée immédiatement par le système central.

---

# 🔄 Comment fonctionne l’assignation ?

L’assignation se fait par **glisser-déposer** :

1. Sélectionner une activité non assignée
2. La déposer sur un animateur, à un moment donné

Une validation automatique est effectuée.

Si une erreur survient (ex : conflit, problème réseau), un message s’affiche et aucune modification n’est conservée.

---

# 📌 Gestion des indisponibilités

Les absences et indisponibilités sont gérées via des événements internes appelés **PartnerEvent**.

Ils peuvent représenter :

* Une absence
* Une réunion interne
* Un blocage horaire
* Une note organisationnelle

Il n’existe pas de système séparé d’absences : tout passe par ce mécanisme.

---

# 🔍 Filtres et affichage

L’application permet de filtrer :

### Les animateurs

* Par rôle métier (secteur)
* Par nom

### Les activités

* Toutes
* Par type d’activité

L’affichage par défaut dépend du profil :

* Animateur → planning de son secteur
* Manager → planning de son secteur
* Organisateur → planning complet

---

# 🔄 Synchronisation

L’application fonctionne exclusivement en ligne.

* Chaque action est validée en temps réel
* Il n’y a pas de mode hors connexion
* Il n’y a pas de sauvegarde locale

Si deux personnes modifient le planning simultanément, la dernière modification validée par le système prévaut.

---

# 📜 Historique

L’historique des modifications n’est pas disponible dans l’application mobile.

Il reste accessible via l’interface web Discope.

---

# 🔐 Sécurité

* Authentification via session sécurisée
* Vérification des permissions côté serveur
* Aucune action critique ne dépend uniquement de l’application mobile

---

# 🚫 Limitations connues

L’application ne permet pas :

* La suppression d’une activité planifiée
* La gestion avancée des conflits simultanés
* Le fonctionnement hors ligne
* L’historique intégré

Elle est volontairement conçue comme un outil mobile opérationnel, simple et rapide.

---

Si tu veux, je peux maintenant :

* soit la réécrire dans le style exact que tu utilises dans ta doc Discope
* soit l’intégrer dans un format cohérent avec ta structure eQual / produit
* soit la rendre encore plus orientée “manuel utilisateur” avec cas concrets.
