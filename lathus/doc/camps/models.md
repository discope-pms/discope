# Modèles de camps

Base de configuration d'un camp :

- Produits :
  - Camp classique : Camp complet, Samedi matin, Liaison entre deux camps
  - Camp CLSH : Journée camp
- Compétences requises : compétences que l'enfant doit avoir pour s'inscrire au camp
- Documents requis : documents nécessaires à l'inscription de l'enfant au camp
- Type de camp : Sport, Cirque, Culture, Environnement, Équitation, Accueil & Loisir, Autre
- Ratio employé : nombre max d'enfants par groupe
- Quota ASE : nombre max d'enfants de l'Aide Sociale à l'Enfance

## Rôle métier

Le modèle de camp est le gabarit de référence utilisé pour créer les camps de la saison.

Il centralise les règles qui doivent être reproduites de façon homogène :

- offre commerciale ;
- documents demandés ;
- compétences requises ;
- contraintes d'encadrement ;
- logique CLSH ou non CLSH.

## Ce que le modèle pilote

Lorsqu'un camp est créé à partir d'un modèle, il hérite de choix structurants :

- les produits à facturer selon le type de camp ;
- les documents attendus pour valider les inscriptions ;
- les compétences exigées pour les enfants ;
- le type fonctionnel du camp ;
- les limites d'encadrement et certains quotas.

## Produits associés

La documentation historique distingue les produits suivants :

### Camp classique

- camp complet ;
- samedi matin ;
- liaison entre deux camps.

### Camp CLSH

- journée camp.

Ces choix structurent ensuite les lignes d'inscription et la tarification.

## Compétences requises

Le modèle peut imposer des compétences nécessaires pour l'inscription à certains camps.

Exemples fonctionnels mentionnés dans la documentation :

- niveau attendu pour certaines activités ;
- exigences liées aux camps d'équitation ;
- autres prérequis propres à une activité ou à une thématique.

## Documents requis

La liste des documents demandés à l'inscription découle du modèle de camp.

Effet métier :

- les inscriptions héritent d'une checklist documentaire ;
- la complétude de cette checklist alimente la validation du dossier ;
- les équipes peuvent suivre plus simplement les pièces manquantes.

## Type de camp

Le type de camp sert à classifier l'offre, par exemple :

- sport ;
- cirque ;
- culture ;
- environnement ;
- équitation ;
- accueil & loisir ;
- autre.

Cette classification a un intérêt de pilotage, de communication et parfois de filtrage.

## Ratio employé

Le ratio employé exprime le nombre maximal d'enfants par groupe ou par encadrant.

Il influence directement :

- la capacité des groupes ;
- le nombre de groupes nécessaires ;
- la cohérence d'exploitation du camp.

## Quota ASE

Le quota ASE limite le nombre d'enfants relevant de l'Aide Sociale à l'Enfance pour un camp donné.

Il sert de garde-fou métier au moment des inscriptions.

## Lecture DEV

La documentation historique décrit `CampModel` comme un objet de référence et non un simple catalogue.

En pratique, cela signifie qu'un changement sur le modèle peut avoir des effets en cascade sur :

- les camps créés ensuite ;
- la liste des documents requis ;
- les produits et tarifs mobilisés ;
- certains contrôles à l'inscription.
