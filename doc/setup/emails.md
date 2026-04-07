## Configuration des emails

**1. Compte email principal pour l'envoi**

- Un compte email unique est configuré dans le fichier `config.json`.
- Ce compte est utilisé pour envoyer tous les emails générés automatiquement par Discope (ex. discope@kaleo-asbl.be).

**2. Destinataires spécifiques pour le reporting et les erreurs**

- Toujours dans `config.json`, des adresses email spécifiques sont définies pour recevoir :
    - Les rapports automatiques (ex. bilans, récapitulatifs)
    - Les notifications d'erreurs (ex. échec d'envoi ou problème technique)

**3. Adresses d'envoi personnalisables par équipe**

- Chaque équipe de gestion peut avoir une ou deux adresses d'expédition configurées.
- Lors de l'envoi de documents (devis, factures, contrats, etc.), l'utilisateur peut choisir l'adresse d'envoi à utiliser parmi celles définies pour son équipe.

**4. Adresse en copie systématique pour vérification**

- Chaque équipe dispose également d'une adresse en copie (CC).
- Cette adresse reçoit une copie systématique de tous les messages envoyés aux clients.
    - Objectif : vérification que le message a bien transité par le serveur SMTP.
    - Utile en cas de réclamation d'un client (non-réception) : on peut confirmer que le message est bien parti, et exclure un bug Discope.
