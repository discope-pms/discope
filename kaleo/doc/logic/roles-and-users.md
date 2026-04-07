## Rôles & Utilisateurs

Les opérateurs sont des utilisateurs, pouvant s'identifier sur la
plateforme et disposant de certains droits.

A un opérateur, sont assignés :

-   Une langue (FR par défaut)

-   Une liste de centres : Pour la plupart des utilisateurs, cette liste
    ne compte qu'un seul centre, mais certains opérateurs doivent
    pouvoir encoder des éléments afférents à différents centre.

-   Une liste de rôles (groupes) : les rôles ont une incidence sur les
    menus, vues et actions disponibles.

Les droits sont définis de manière globale :

-   Un opérateur ne peut encoder des réservations et factures que pour
    les centres auxquels il est assigné

-   Les actions et les vues sont assignées sur base des centres : un
    opérateur ne voit que les informations des centres auxquels il est
    assigné

-   Les objets sont filtrés sur base des centres : un opérateur ne voit
    que les objets liés aux centres auxquels il est assigné
    (réservations, factures, contrats, ...; organisés par centres et
    catégorie de centre)

### Rôles

Les droits sont attribués en regroupant les utilisateurs en fonctions de
leurs prérogatives, en les assignant à un ou plusieurs rôles.
Les rôles sont définis comme des groupes organisés de manière
hiérarchique. Un utilisateur peut avoir plusieurs rôles, et les
permissions résultantes correspondent à celles des rôles les plus
permissifs).


| Rôle                                         | Permissions                                                                                        |
|----------------------------------------------|----------------------------------------------------------------------------------------------------|
| Comptabilité / Administrateurs               | Modification des paramètres liés à la compta (règles comptables, plan comptable, règles de TVA, …) |
| Comptabilité / Facturation / Administrateurs | Création de factures (et notes de crédit) et envoi de factures aux clients                         |
| Comptabilité / Facturation / Utilisateurs    | Consultation des factures                                                                          |
| Ventes / Réservations / Utilisateurs         | Création et consultation des réservations                                                          |
| Ventes / Catalogue / Utilisateurs            | Consultation des produits du catalogue                                                             |
| Ventes / Catalogue / Administrateurs         | Modification des produits et ajout de nouveaux produits au catalogue                               |
| Ventes / Listes de prix / Utilisateurs       | Consultation des prix                                                                              |
| Ventes / Listes de prix / Administrateurs    | Modification des prix et des grilles tarifaires                                                    |
| Ventes / Réductions / Utilisateurs           | Consultation des réductions et listes de réductions                                                |
| Ventes / Réductions / Administrateurs        | Modification et création des listes de réductions                                                  |
| Ventes / Saisons / Utilisateurs              | Consultation des saisons et listes de saisons                                                      |
| Ventes / Saisons / Administrateurs           | Modification et création des saisons                                                               |
| Point de Vente / Administrateurs             | Encodage des consommations par la caisse                                                           |
| Point de Vente / Utilisateurs                | Encodage des consommations par la caisse                                                           |
| Settings                                     | Modification de la configuration globale                                                           |

