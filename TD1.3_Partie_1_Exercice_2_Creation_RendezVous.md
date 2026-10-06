# TD1.3 — Partie 1 — Exercice 2 : création d’un rendez-vous dans Toubilib

## 1. Données fournies par l’application cliente

L’application cliente doit fournir :

- `praticienId` : identifiant du praticien, de type `string` ;
- `patientId` : identifiant du patient, de type `string` ;
- `dateHeureDebut` : date et heure souhaitées, converties en `DateTimeImmutable` ;
- `motifVisite` : motif du rendez-vous, de type `string`.

Ces quatre données sont obligatoires. Le client ne fournit pas l’identifiant du rendez-vous, sa durée, sa date de création ou son état. L’identifiant est généré par l’application, la durée dépend du motif et du praticien, la date de création correspond au moment de la création et l’état initial est `PLANIFIE`.

## 2. Vérifications métier

Avant de créer le rendez-vous, il faut vérifier que :

- le praticien existe ;
- le patient existe ;
- le motif est accepté par le praticien ;
- la date demandée est future ;
- le jour et l’heure appartiennent aux périodes pendant lesquelles le praticien reçoit des patients ;
- le praticien est disponible pendant toute la durée calculée du rendez-vous ;
- le rendez-vous demandé ne chevauche aucun autre rendez-vous actif du praticien.

Les contrôles de présence, de type et de format ne sont pas des règles métier, mais ils doivent être réalisés avant l’appel du service.

## 3. Composants responsables des validations

- `CreateRdvDTO` contrôle la présence, le type et le format des données provenant de la requête HTTP.
- Un validateur de création de rendez-vous utilise les repositories pour vérifier l’existence du praticien et du patient.
- L’entité `Praticien` vérifie le motif, calcule la durée correspondante et contrôle ses horaires ainsi que sa disponibilité.
- L’entité `RendezVous` vérifie la cohérence de la création et le chevauchement avec un autre rendez-vous.
- `ServiceRendezVous` coordonne ces contrôles, mais ne doit pas contenir directement les règles métier.

## 4. Création et persistance de l’entité

`ServiceRendezVous` charge le patient et le praticien par l’intermédiaire des ports de sortie, demande au domaine de créer l’entité `RendezVous`, puis appelle `RendezVousRepositoryInterface::save()`.

La création est donc coordonnée par le service, tandis que l’enregistrement en base est effectué par l’adaptateur `RendezVousRepository`.

## 5. DTO et port d’entrée

Deux DTO sont nécessaires :

- `CreateRdvDTO` transporte les données nécessaires à la création ;
- `RendezVousDTO` transporte le rendez-vous créé vers l’adaptateur d’entrée.

La méthode du port d’entrée peut être définie ainsi :

```php
public function creer(CreateRdvDTO $dto): RendezVousDTO;
```

Cette méthode peut déclencher les exceptions suivantes :

- `ValidationException` si les données reçues sont absentes ou mal formées ;
- `PraticienNotFoundException` si le praticien n’existe pas ;
- `PatientNotFoundException` si le patient n’existe pas ;
- `MotifRendezVousInvalideException` si le motif n’est pas accepté ;
- `DateRendezVousInvalideException` si la date ou l’horaire ne convient pas ;
- `CreneauRendezVousIndisponibleException` si le créneau chevauche un autre rendez-vous ;
- une exception de persistance si l’enregistrement en base échoue.
