# TD1.3 — Partie 2 — Exercice 2 : validation métier d’un rendez-vous

## Condition de chevauchement

Deux rendez-vous A et B se chevauchent lorsque :

```text
debutA < finB ET finA > debutB
```

Les comparaisons sont strictes : si A se termine exactement au moment où B commence, les deux rendez-vous sont compatibles.

Un rendez-vous annulé ne rend pas le praticien indisponible.

## Chargement de l’agenda

Le repository remplit l’agenda du praticien au moment où le validateur prépare la création du rendez-vous. Le port de sortie expose pour cela `findByIdWithRendezVousForDate()`.

Il n’est pas nécessaire de charger tous les rendez-vous du praticien. Seuls ceux du jour demandé peuvent chevaucher le nouveau rendez-vous, puisque les rendez-vous doivent commencer et se terminer pendant la même journée de travail.

## Règles configurées

Le schéma fourni ne contient aucune table de motifs ou d’horaires. La configuration minimale utilise donc le motif `Consultation` avec une durée de 30 minutes, du lundi au vendredi entre 08:00 et 18:00. Ces valeurs sont des paramètres du constructeur de `Praticien` et peuvent être remplacées sans modifier les règles de validation.
