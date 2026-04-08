# Réductions, Aides & Parrains

## Adaptateurs de prix (PriceAdapter)

- Réductions manuelles, Fidélité, Aides collectivités, CAF / MSA.
- Contraintes : un seul `percent` par inscription, sponsors en `amount`.

## Rôle métier

Les adaptateurs de prix servent à moduler le coût final de l'inscription sans changer la structure de base des produits vendus.

Ils couvrent plusieurs logiques :

- réduction commerciale directe ;
- fidélité ;
- aide publique ou territoriale ;
- participation d'un tiers payeur.

## Types d'effets

Deux grandes familles ressortent de la documentation historique :

- les effets qui modifient directement le prix de l'inscription ;
- les aides qui n'altèrent pas immédiatement le prix affiché, mais viennent compléter le financement global du dossier.

## Cas documentés

### Réductions directes

Les réductions de type :

- autre ;
- réduction fidélité

agissent directement sur le prix de l'inscription, en montant ou en pourcentage selon la configuration.

### Aides

Les aides du type :

- aide commune ;
- aide communauté de communes ;
- aide CAF ;
- aide MSA

ne sont pas décrites comme une simple baisse de prix commerciale. Elles participent plutôt à l'équilibre financier global du dossier.

## Sponsors

- Montant et type de sponsor.
- Alimentent les adaptateurs de prix.

## Sponsors et tiers

Les sponsors sont modélisés comme des participations en montant fixe (`amount`).

Ils servent à représenter des prises en charge externes ou des contributions spécifiques qui complètent la structure financière de l'inscription.

## Comité d’entreprise (WorksCouncil)

- Peut surclasser la classe de camp appliquée.

## Comité d'entreprise

Le comité d'entreprise peut améliorer la classe de camp appliquée à l'inscription.

Effet métier :

- la classe tarifaire devient plus avantageuse ;
- la sélection de prix peut basculer sur un tarif plus favorable.

## Contraintes métier

La documentation historique met en avant deux garde-fous :

- un seul adaptateur de type `percent` par inscription ;
- les sponsors restent en montant fixe.

Cela limite les cumuls incohérents et garde une logique de calcul lisible.

## Lecture DEV

Même si le package `lathus` n'ajoute pas ici de provider spécifique, cette page reste importante pour les DEV car ces mécanismes influencent :

- le calcul du prix affiché ;
- la composition des financements ;
- certaines statistiques par aides, sponsors ou classes tarifaires.
