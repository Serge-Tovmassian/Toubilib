# TD1.2 — Partie 1 — Exercice 1 : injection de dépendances dans JIRA

## 1. Création de la connexion PDO

La connexion est définie dans `jira\adapters\config\ContainerConfig`. Le conteneur PHP-DI lit les variables `DRIVER`, `HOST`, `DATABASE`, `USERNAME` et `PASSWORD`, construit le DSN, puis crée l’instance de `PDO` identifiée par la clé `jira.pdo`.

## 2. Objets utilisant PDO

`PgUserStoryRepository` et `PgOwnerRepository` reçoivent tous les deux l’instance de `PDO` dans leur constructeur. PHP-DI leur transmet la dépendance avec `DI\get('jira.pdo')`. Les repositories n’ont donc pas à créer eux-mêmes la connexion.

## 3. Construction de `UserStoryService`

Le constructeur attend :

- un `UserStoryRepository` ;
- un `OwnerRepository` ;
- un `CreateUserStoryValidator`.

Le service est défini dans `ContainerConfig`. PHP-DI construit `UserStoryService` et lui transmet les trois objets déclarés dans le conteneur. Le validateur reçoit lui-même le port `OwnerRepository`.

## 4. Construction des actions

Slim demande les actions au conteneur PHP-DI lorsqu’une route doit être exécutée. Les actions reçoivent dans leur constructeur un `UserStoryServiceInterface`. Le service, les repositories, le validateur et PDO sont créés par le conteneur au moment où ils sont nécessaires, à partir des définitions enregistrées dans `ContainerConfig`.
