# Apps associees aux reservations

## App "Managers" - releves compteurs

Le document source mentionne une application dediee aux releves compteurs, sans detailler davantage le flux fonctionnel dans cette version de la documentation.

Cette page sert donc de point d'entree pour completer ulterieurement :

- le perimetre exact des releves;
- le mode d'encodage;
- le lien avec les statistiques et la facturation.

## App "Guests List" - fiches d'hebergement

Une application publique permet aux clients d'encoder la liste des participants d'une reservation donnee. Une fois soumise, cette liste alimente la composition d'hebergement de la reservation correspondante.

## Fonctionnement

A la confirmation d'une reservation, un email contenant un lien de demande de connexion peut etre envoye a un contact de la reservation.

Le flux decrit est le suivant :

- envoi automatique apres le contrat ou envoi manuel;
- authentification par demande de connexion;
- acces limite aux emails enregistres pour la reservation;
- possibilite d'ajouter d'autres emails autorises depuis l'application;
- soumission finale de la liste quand elle est complete;
- export XLS disponible tant que la reservation n'est pas terminee.

L'application est pensee pour mobile, tablette et desktop, avec un en-tete rappelant le client, les dates et le centre.

## Securite

La strategie d'acces est passwordless :

- lien a usage limite;
- acces reserve aux emails connus dans Discope;
- validation prealable de l'adresse email demandee.

## Completude et validation

Le pourcentage de completude informe sur l'avancement par rapport au nombre de participants attendu, mais la soumission depend surtout de la presence des donnees obligatoires.

Contraintes metier reprises dans le document source :

- au moins une personne responsable doit etre identifiee;
- toutes les lignes creees doivent etre completes;
- le NISS n'est pas exige pour les moins de 15 ans;
- les adresses ne sont demandees que pour les responsables.

## Import dans la composition

Quand la liste est marquee comme complete, son statut evolue et les donnees sont utilisees pour mettre a jour la composition d'hebergement de la reservation.
