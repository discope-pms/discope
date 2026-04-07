Certains éléments qui apparaissent dans les documents et messages
envoyés aux clients doivent être traduits :

-   Les signatures des équipes de gestion doivent être traduites

-   La description des catégories tarifaires

Les documents sont générés à la volées et sur base de la langue demandée
(via le front-end; par défaut selon la langue du client ou du contact).

La logique de traduction est la suivante :

-   Dans les templates HTML, les termes dépendant de la langue sont
    préfixés par i18n\_

-   La langue utilisée est celle fournie en paramètre (qui devrait être
    la langue du client)

-   Les termes de traduction sont stockés dans la configuration :
    settings lodging.locale.terms

-   Les extraits de texte et notices correspondent aux templates
    (`Communication > Template > Template parts`), et les valeurs liées
    aux traductions dans le controleurs (i18n\_) sont injectées dans les
    templates HTML (pour utiliser les valeurs traduites)

#### `Configuration > Communication > Templates > Tous les modèles`

Modèle: contract.contract

-   En-tête du contrat : header

-   Notice du contrat : notice

Les traductions des termes individuels sont dans la partie « i18n » de
la configuration.

#### `Configuration > Paramètres > Valeurs des paramètres`

lodging.locale.i18n.\*
