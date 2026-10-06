# TD1.2 — Partie 1 — Exercice 3 : conception des fonctionnalités 1, 2 et 3

## Fonctionnalité 1 : lister les praticiens

- Entités : `Praticien` et `Specialite`.
- Port d’entrée : `ServicePraticienInterface::lister(): array`.
- Port de sortie : `PraticienRepositoryInterface::findAll(): array`.
- DTO : `PraticienResumeDTO`, contenant l’identifiant, le nom, le prénom, le titre, la ville et la spécialité.
- Adaptateur d’entrée : `ListerPraticiensAction`, associé à `GET /praticiens`.
- Adaptateur de sortie : `PraticienRepository`, qui lit les tables `praticien` et `specialite` avec PDO.
- Erreurs : une erreur PDO peut se produire dans le repository et est transformée en réponse `500` par l’action. Une liste vide est un résultat valide et produit `200` avec `[]`.

## Fonctionnalité 2 : obtenir le détail d’un praticien

- Entités : `Praticien` et `Specialite`.
- Port d’entrée : `ServicePraticienInterface::obtenirParId(string $id): PraticienDTO`.
- Port de sortie : `PraticienRepositoryInterface::findById(string $id): ?Praticien`.
- DTO : `PraticienDTO`, contenant les coordonnées, la spécialité, le RPPS, la structure et les informations d’accueil des nouveaux patients.
- Adaptateur d’entrée : `ObtenirPraticienAction`, associé à `GET /praticiens/{id}`.
- Adaptateur de sortie : `PraticienRepository`.
- Erreurs : l’action refuse un identifiant vide avec `400`. Si le repository retourne `null`, le service déclenche `PraticienNotFoundException`, traduite en `404`. Une erreur PDO produit `500`.

## Fonctionnalité 3 : obtenir le détail d’un rendez-vous

- Entité : `RendezVous` et son état `EtatRendezVous`.
- Port d’entrée : `ServiceRendezVousInterface::obtenirParId(string $id): RendezVousDTO`.
- Port de sortie : `RendezVousRepositoryInterface::findById(string $id): ?RendezVous`.
- DTO : `RendezVousDTO`, contenant les identifiants du rendez-vous, du praticien et du patient, les dates, la durée, l’état et le motif.
- Adaptateur d’entrée : `ObtenirRendezVousAction`, associé à `GET /rendez-vous/{id}`.
- Adaptateur de sortie : `RendezVousRepository`.
- Erreurs : l’action refuse un identifiant vide avec `400`. Si le repository retourne `null`, le service déclenche `RendezVousNotFoundException`, traduite en `404`. Une erreur PDO produit `500`.
