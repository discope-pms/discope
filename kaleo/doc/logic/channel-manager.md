# Channel Manager

## Perimetre

Kaleo utilise Cubilis comme channel manager pour gerer les reservations OTA, notamment via Booking.com, Airbnb et le portail Cubilis.

Le principe general repris dans le document source est le suivant :

- Discope reste proprietaire des disponibilites reelles;
- Cubilis porte les tarifs OTA et la diffusion vers les canaux;
- les paiements en ligne peuvent transiter par Stripe via Cubilis.

La strategie operationnelle decrite par Kaleo consiste a ouvrir les disponibilites OTA sur une fenetre limitee, en pratique au maximum trois mois a l'avance, afin de preserver la priorite aux groupes.

## Logique de propriete des donnees

La repartition des responsabilites est explicitee ainsi :

- Discope detient le nombre d'unites reellement disponibles par date;
- Cubilis porte les tarifs utilises pour les ventes OTA.

En consequence :

- les equipes ne modifient pas directement le planning Cubilis sauf cas de correction manuelle;
- les reservations provenant du channel manager doivent etre gerees dans Discope pour tout ce qui concerne l'assignation reelle;
- certaines annulations restent pilotees uniquement cote OTA.

## Structures de synchronisation

Le document source mentionne trois objets de mapping indispensables :

- `Property` pour relier une propriete Cubilis a un centre Discope;
- `RoomType` pour relier les types de chambres Cubilis aux unites locatives Discope;
- `Extra Service` pour faire correspondre les extras Cubilis aux produits Discope.

Les conventions relevees incluent notamment :

- petit dejeuner inclus par defaut selon la configuration cible;
- carte de membre comme extra optionnel;
- taxe de sejour comme extra obligatoire quand elle existe;
- gestion des tarifs au niveau des types d'hebergement et non des unites individuelles.

## Synchronisation Cubilis vers Discope

### Import des reservations

Les reservations sont recuperees depuis Cubilis via l'API OTA, puis transformees dans Discope par un controleur dedie.

Le document insiste sur plusieurs points :

- une reservation peut contenir plusieurs `room_stays`;
- chaque `room_stay` reference un `room_type`;
- les montants OTA recus sont consideres comme la source de verite pour la reservation importee.

### Resolution des clients

L'identification du client se fait sur les donnees d'identite fournies par le channel manager :

- nom;
- prenom;
- adresse;
- autres coordonnees disponibles.

La resolution reste volontairement plus souple que pour une reservation creee directement dans Discope, car les informations OTA peuvent etre incompletes.

### Prix et regles comptables

Pour les reservations OTA :

- le prix retenu dans Discope est celui transmis par Cubilis;
- les listes de prix Discope servent surtout a recuperer le cadre comptable applicable;
- il faut pouvoir conserver des produits techniques a prix nul pour reconstituer la reservation importee sans recalcul automatique.

Le document source cite notamment les produits techniques `SEJ_OTA`, `NUIT_OTA` et `PTDEJ_OTA`.

### Identification des unites locatives

L'assignation s'appuie sur le mapping entre `RoomType` et unites locatives reelles.

La regle metier decrite est :

- prendre la premiere unite locative disponible pour la periode;
- si aucune unite n'est disponible, creer malgre tout la reservation et signaler un surbooking;
- tenir compte des relations hierarchiques entre unites parent/enfant lors des blocages.

### Services, taxes et paiements

Le document source precise que :

- les extras OTA peuvent etre convertis en groupes de services Discope;
- la taxe de sejour est ajoutee automatiquement si elle n'est pas fournie;
- un financement et un paiement peuvent etre crees lorsqu'un paiement est deja present cote Cubilis.

Pour Stripe, certaines metadonnees de PSP doivent etre recuperees afin de stocker les frais de transaction sur le paiement.

### Annulation et modification

Une annulation OTA se traduit par une reservation remise a disposition par Cubilis avec un statut modifie. Meme si la reservation a ete annulee avant son import initial, elle doit etre conservee pour archivage et pour gerer un eventuel remboursement.

Une modification OTA est traitee comme une recreation logique de la reservation, a numero externe constant, avec conservation des paiements deja encaisses lorsque c'est pertinent.

Si le sejour est deja en cours, le document recommande de ne pas appliquer automatiquement les changements et d'emettre une alerte pour traitement manuel.

## Synchronisation Discope vers Cubilis

La synchronisation retour concerne la disponibilite par type de chambre.

Le mecanisme decrit est :

1. recalculer le nombre d'unites disponibles a partir des unites locatives reelles;
2. detecter les evenements qui affectent cette disponibilite;
3. pousser une mise a jour `set_availability` vers Cubilis.

Les evenements suivis incluent notamment :

- passage en option;
- retour en devis;
- annulation;
- modification d'assignation;
- creation ou suppression de blocages techniques.

## Limitations et points de vigilance

Le document source liste plusieurs limites structurelles :

- Cubilis n'accepte pas les mises a jour au-dela de deux ans;
- les synchronisations sont differees, ce qui peut creer des desalignements temporaires;
- certains statuts OTA ne sont pas pleinement exploitables dans Discope;
- les mappings `RoomType` et `Extra Service` doivent etre configures manuellement;
- un surbooking peut necessiter une double intervention, dans Discope et dans Cubilis.

Dans les situations d'erreur technique, une replanification automatique est prevue. Pour les modifications importantes ou ambigues, la verification manuelle par les equipes reste indispensable.

## Cas particuliers

### Hostelworld

Pour repondre aux contraintes Hostelworld, certaines unites doivent exister comme veritables lits de dortoir dans le referentiel.

### Integration Stripe

Le document souligne qu'une connexion Stripe deja creee dans Cubilis n'est pas reellement modifiable : il faut la supprimer puis la recreer pour changer de credentials.

### Resynchronisation des calendriers

Une action manuelle permet de forcer une resynchronisation pour une propriete et un mois donne. Cette fonctionnalite est decrite comme couteuse et doit rester exceptionnelle.
