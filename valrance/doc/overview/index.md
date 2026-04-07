# Vue d'ensemble

Le package `valrance` ne redéfinit pas tout Discope. Il ajoute surtout des adaptations ciblées pour le Relais Valrance autour de trois sujets :

- les impressions de documents commerciaux ;
- les exports statistiques métier ;
- certaines présentations spécifiques des réservations.

## Ce que contient le package

Les éléments effectivement présents dans le package sont :

- une app `Statistiques` visible dans le lanceur d'applications ;
- un menu latéral dédié avec les entrées `Contrats & Réservations` et `Fiches récap.` ;
- des data providers spécifiques pour les exports statistiques ;
- des providers d'impression pour réservation, contrat et facture ;
- des templates HTML d'impression dédiés ;
- un document métier source `documentation.md`.

## Ce que le package ne contient pas

Le package ne redéfinit pas ici de classes ORM propres. Il s'appuie surtout sur :

- des vues ;
- des providers de données ;
- des templates de documents.

Cette structure indique que Valrance repose principalement sur des surcharges de restitution et de reporting, plus que sur un modèle métier distinct.
