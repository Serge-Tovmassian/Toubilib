# Toubilib

API REST de gestion de rendez-vous médicaux (BUT3 Informatique, IUT Nancy-Charlemagne, module DWM).
PHP 8.5, Slim 4, PHP-DI, PostgreSQL, architecture hexagonale. Back-end uniquement.

## Lancement

```
cd toubilib
cp toubilib.env.dist toubilib.env     # les valeurs par défaut suffisent pour une démo locale
docker compose up -d --build
```

- API : http://localhost:7080
- Adminer : http://localhost:8880 (serveur `toubilib.db`)
- PostgreSQL : port 5532

`CORS_ORIGINS` liste (séparée par des virgules) les origines navigateur autorisées ; sans correspondance, aucun en-tête CORS n'est envoyé.

La base est créée et peuplée automatiquement (`app/sql/toubilib.schema.sql` puis `toubilib.data.sql`) au premier démarrage du conteneur PostgreSQL.
Pour repartir de zéro : `docker compose down -v && docker compose up -d`.

Authentification : `JWT_SECRET` (32 caractères minimum) signe les jetons. Une fois la base prête, créer les comptes de démo
(un par patient et par praticien, email de la fiche, mot de passe `toubilib` par défaut) :

```
docker compose exec api.toubilib php bin/seed-users.php
```

`POST /auth/login` avec `{"email","password"}` renvoie un JWT valable 1 h (`Authorization: Bearer …`).

Une collection Bruno prête à l'emploi est dans `toubilib/bruno` (environnement `local`) : lancer « Login patient » avant les requêtes 3 à 7, « Login praticien » avant les requêtes 8 à 10 ; le jeton est conservé automatiquement.

Tests : `cd toubilib/app && php vendor/bin/phpunit tests`

## Architecture

```
app/src/
  domain/         entités et règles métier (Praticien, RendezVous, exceptions)
  application/    ports api (services), ports spi (repositories), use cases, DTO, validateurs
  adapters/       actions Slim (HTTP) et repositories PDO
app/config/       routes, définitions PHP-DI, bootstrap (CORS, erreurs)
```

Les réponses JSON suivent un format HATEOAS : `{"type": "ressource|collection", ..., "links": {...}}`.

## Routes

Toutes les routes, sauf `POST /auth/login`, exigent un en-tête `Authorization: Bearer <jwt>` (401 sinon).

Droits d'accès (403 sinon) :

| Route | Accès |
|---|---|
| `GET /praticiens`, `/praticiens/{id}`, `/praticiens/{id}/disponibilites` | tout utilisateur authentifié |
| `GET /praticiens/{id}/agenda`, `/creneaux`, `POST /praticiens/{id}/indisponibilites` | ce praticien uniquement |
| `GET /patients/{id}/rdvs` | ce patient uniquement |
| `POST /rdvs` | un patient pour lui-même, ou un praticien dans son propre agenda |
| `GET /rdvs/{id}`, `PATCH /rdvs/{id}/annuler` | le patient ou le praticien du RDV |
| `PATCH /rdvs/{id}/honorer`, `/ignorer` | le praticien du RDV |

| Méthode | Route | Description |
|---|---|---|
| GET | `/praticiens?specialite=&ville=` | Liste des praticiens, filtrable |
| GET | `/praticiens/{id}` | Détail d'un praticien |
| GET | `/praticiens/{id}/disponibilites?date=YYYY-MM-DD&motif=CI` | Créneaux libres d'une journée pour un motif |
| GET | `/praticiens/{id}/agenda?debut=&fin=` | Agenda sur une période (dates ISO 8601), avec le nom du patient de chaque RDV |
| GET | `/praticiens/{id}/creneaux?debut=&fin=` | Créneaux occupés sur une période |
| POST | `/praticiens/{id}/indisponibilites` | Bloque une demi-journée `{"date","periode":"matin\|apres-midi"}` |
| GET | `/patients/{id}/rdvs` | Historique des consultations d'un patient |
| POST | `/rdvs` | Crée un RDV `{praticien_id, patient_id, date_heure_debut, motif_visite}` |
| GET | `/rdvs/{id}` | Détail d'un RDV |
| PATCH | `/rdvs/{id}/annuler` | Annule un RDV planifié et futur |
| PATCH | `/rdvs/{id}/honorer` | Marque le RDV comme honoré |
| PATCH | `/rdvs/{id}/ignorer` | Marque le RDV comme non honoré |

Motifs de visite : `CI` (30 min), `C0` (20 min), `CS` (15 min).
Codes d'erreur : 400 requête invalide, 404 ressource inconnue, 409 créneau indisponible ou transition d'état interdite, 422 règle métier violée.

## Tableau de bord des fonctionnalités

| # | Fonctionnalité | État |
|---|---|---|
| 1 | Lister les praticiens | fait |
| 2 | Détails d'un praticien | fait |
| 3 | Détails d'un rendez-vous | fait |
| 4 | Agenda d'un praticien sur une période | fait |
| 5 | Créer un rendez-vous (motif, règles métier) | fait |
| 6 | Annuler un rendez-vous | fait |
| 7 | Créneaux occupés d'un praticien sur une période | fait |
| 8 | Recherche par spécialité et/ou ville | fait |
| 9 | Cycle de vie des rendez-vous (honoré, ignoré) | fait |
| 10 | Historique des consultations d'un patient | fait |
| 11 | Disponibilités d'un praticien pour un motif et un jour | fait |
| 12 | Indisponibilités temporaires d'un praticien | fait |

## Contributions

| Membre | Réalisations |
|---|---|
| Serge Tovmassian (projet réalisé seul) | Ensemble des 12 fonctionnalités : architecture hexagonale, routes et HATEOAS, CORS, création / annulation / honorer / ignorer, recherche, historique, disponibilités, indisponibilités, tests, Docker, README |
