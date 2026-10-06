# TD1.2 — Partie 1 — Exercice 2 : dépendances de la fonctionnalité 6

## 1. Action d’annulation

`AnnulerRendezVousAction` reçoit un `ServiceRendezVousInterface`. L’objet concret transmis par le conteneur est une instance de `ServiceRendezVous`.

## 2. Service métier

`ServiceRendezVous` reçoit un `RendezVousRepositoryInterface`. L’objet concret transmis est une instance de `RendezVousRepository`.

## 3. Adaptateur de persistance

`RendezVousRepository` reçoit une instance de `PDO`. Cette connexion est créée une seule fois par le conteneur, puis injectée dans les repositories qui en ont besoin.

## 4. Configuration de la connexion

Les informations nécessaires sont le pilote, l’hôte, le port, le nom de la base, le nom d’utilisateur et le mot de passe. Elles peuvent être fournies par des variables d’environnement, par un fichier `.env` non versionné ou par la configuration du service Docker. Le fichier `settings.php` centralise ces valeurs, puis `services.php` les utilise pour construire PDO.
