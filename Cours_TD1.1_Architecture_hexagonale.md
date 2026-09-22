# Cours complet — TD1.1 Architecture hexagonale avec PHP et Slim

# 1. Le but général du projet Toubilib

Toubilib est une application web de gestion de rendez-vous médicaux. Elle manipule notamment des patients, des praticiens, des spécialités et des rendez-vous.

La fonctionnalité étudiée dans ce TD est la fonctionnalité 6 : annuler un rendez-vous.

Cette fonctionnalité paraît simple, mais elle fait intervenir plusieurs responsabilités :

1. recevoir une demande HTTP envoyée par un client ;
2. vérifier que le rendez-vous demandé existe ;
3. vérifier qu’il peut réellement être annulé ;
4. modifier son état ;
5. enregistrer ce nouvel état dans PostgreSQL ;
6. retourner une réponse JSON avec un statut HTTP correct.

Le but principal du TD n’est donc pas seulement de réussir une requête SQL. Il faut organiser le programme pour séparer clairement le métier, l’application, le Web et la base de données.

# 2. Les technologies utilisées

## PHP 8.5

PHP est le langage utilisé pour programmer le serveur. Le navigateur ou le client HTTP envoie une requête au serveur, PHP exécute le code correspondant, puis le serveur retourne une réponse.

PHP est exécuté côté serveur. Le client ne reçoit pas le code PHP : il reçoit uniquement le résultat produit, ici principalement du JSON.

Exemple de réponse :

```json
{
  "id": "rdv-123",
  "etat": "ANNULE"
}
```

Dans ce projet, PHP est utilisé en programmation orientée objet. Le code est donc organisé avec des classes, des interfaces, des objets, des méthodes, des propriétés et des exceptions.

## Slim 4

Slim est un micro-framework PHP destiné à créer des applications Web et des API HTTP.

Un framework fournit une structure et des outils déjà prêts. Slim permet notamment :

- de démarrer l’application ;
- de déclarer des routes ;
- de recevoir les requêtes HTTP ;
- d’exécuter une action associée à une route ;
- de construire et retourner une réponse HTTP.

Slim est appelé micro-framework parce qu’il impose peu de choses. Il ne crée pas automatiquement les entités, les repositories ou les règles métier. C’est au développeur de choisir l’architecture.

## PostgreSQL

PostgreSQL est le système de gestion de base de données. Il conserve durablement les patients, praticiens, spécialités et rendez-vous.

Les rendez-vous sont stockés dans la table `rdv`. Une ligne de cette table contient notamment :

- l’identifiant du rendez-vous ;
- l’identifiant du patient ;
- l’identifiant du praticien ;
- la date et l’heure ;
- la durée ;
- le motif ;
- le statut.

## PDO

PDO est l’interface standard de PHP permettant de communiquer avec une base de données.

PDO permet de :

- ouvrir une connexion à PostgreSQL ;
- préparer une requête SQL ;
- fournir les paramètres de la requête ;
- exécuter la requête ;
- récupérer les résultats.

Exemple simplifié :

```php
$requete = $pdo->prepare('SELECT * FROM rdv WHERE id = :id');
$requete->execute(['id' => $id]);
$ligne = $requete->fetch();
```

Il faut utiliser des requêtes préparées afin de séparer le code SQL des valeurs reçues et d’éviter les injections SQL.

## Composer

Composer est le gestionnaire de dépendances de PHP. Il télécharge les bibliothèques nécessaires au projet à partir du fichier `composer.json`.

Dans Toubilib, Composer installe notamment Slim, PDO, PHP-DI, Pest et PHPUnit.

Composer configure également l’autoloading PSR-4 : les classes sont chargées automatiquement à partir de leur namespace et de leur emplacement.

La configuration suivante :

```json
"toubilib\\": "./src"
```

signifie que la classe :

```php
toubilib\domain\entities\RendezVous
```

doit se trouver dans :

```text
src/domain/entities/RendezVous.php
```

## Docker et Docker Compose

Docker exécute les différents éléments du projet dans des conteneurs isolés.

Le fichier `docker-compose.yml` décrit trois services :

- `api.toubilib` : serveur PHP exécutant Slim ;
- `toubilib.db` : serveur PostgreSQL ;
- `adminer` : interface graphique permettant d’examiner la base.

Les conteneurs communiquent grâce au réseau Docker `toubilib.net`.

Les ports rendent les services accessibles depuis l’ordinateur :

- port `7080` pour l’API PHP ;
- port `5532` pour PostgreSQL ;
- port `8880` pour Adminer.

## Pest et PHPUnit

Pest et PHPUnit servent à écrire et exécuter des tests automatisés.

Un test vérifie qu’un morceau du programme produit le résultat prévu. Par exemple :

- un rendez-vous futur et planifié peut être annulé ;
- un rendez-vous passé ne peut pas être annulé ;
- un rendez-vous déjà annulé ne peut pas être annulé une seconde fois.

# 3. Comment fonctionne une application Web

Une application Web utilise principalement deux parties :

- le client, par exemple un navigateur, une application mobile ou Postman ;
- le serveur, ici l’application PHP/Slim.

Le client envoie une requête HTTP. Cette requête contient notamment une méthode, une URL, des en-têtes et parfois un corps.

Exemple pour demander l’annulation d’un rendez-vous :

```http
PATCH /rendez-vous/rdv-123/annulation
```

Le serveur traite la demande et retourne une réponse HTTP contenant :

- un code de statut ;
- des en-têtes ;
- éventuellement un corps JSON.

Exemples de statuts :

- `200 OK` : traitement réussi avec une réponse ;
- `204 No Content` : traitement réussi sans contenu ;
- `400 Bad Request` : demande incorrecte ;
- `404 Not Found` : rendez-vous inexistant ;
- `409 Conflict` : opération incompatible avec l’état actuel ;
- `500 Internal Server Error` : erreur technique inattendue.

# 4. Les bases de PHP orienté objet

## Une classe et un objet

Une classe est un modèle. Un objet est une instance créée à partir de ce modèle.

La classe `RendezVous` décrit ce que possède et ce que peut faire un rendez-vous. Chaque rendez-vous concret est un objet différent.

```php
$rendezVous = new RendezVous(
    id: 'rdv-123',
    praticienId: 'praticien-5',
    patientId: 'patient-8',
    dateHeureDebut: new DateTimeImmutable('2030-10-20 14:00:00'),
    duree: 30,
);
```

## Les propriétés

Les propriétés représentent les données conservées par un objet : identifiant, date, durée ou état.

Une propriété `private` ne peut être modifiée directement depuis l’extérieur. Cela protège l’objet contre des changements incohérents.

## Les méthodes

Une méthode représente une opération réalisable par un objet.

```php
$rendezVous->annuler();
```

Il est préférable d’appeler `annuler()` plutôt que de rendre l’état public et de le modifier directement. La méthode peut ainsi vérifier toutes les règles avant d’accepter le changement.

## Le constructeur

La méthode `__construct()` est exécutée lors de la création d’un objet. Elle reçoit les informations nécessaires pour construire un objet cohérent.

## Une interface

Une interface est un contrat. Elle annonce les méthodes qu’une classe doit fournir, sans définir leur fonctionnement complet.

```php
interface ServiceRendezVousInterface
{
    public function annuler(string $id): RendezVousDTO;
}
```

Une classe qui implémente cette interface promet de fournir la méthode `annuler()` avec cette signature.

## Une énumération

Une énumération limite une valeur à une liste précise de possibilités.

```php
enum EtatRendezVous: int
{
    case ANNULE = -1;
    case PLANIFIE = 0;
    case HONORE = 1;
    case NON_HONORE = 2;
}
```

Elle évite les valeurs libres et incohérentes comme `presque_annule`, `finii` ou `10`.

## Une exception

Une exception signale qu’une opération ne peut pas se dérouler normalement.

Exemples :

- `RendezVousNotFoundException` : aucun rendez-vous ne correspond à l’identifiant ;
- `TransitionEtatRendezVousException` : le changement d’état demandé est interdit.

Une exception remonte dans les différentes couches jusqu’à ce qu’un composant sache la traiter. Dans une action HTTP, elle peut être traduite en réponse `404` ou `409`.

# 5. Le modèle MVC

MVC signifie Modèle, Vue, Contrôleur.

## Le modèle

Le modèle représente les données et les règles importantes de l’application.

Dans Toubilib, l’entité `RendezVous` fait partie du modèle. Elle contient l’état du rendez-vous et les règles autorisant ou interdisant son annulation.

## La vue

La vue présente le résultat à l’utilisateur.

Dans une application Web classique, la vue peut être une page HTML. Dans l’API Toubilib, il n’y a pas réellement de page graphique : la représentation retournée est du JSON. Le JSON joue donc le rôle de représentation du résultat.

## Le contrôleur

Le contrôleur reçoit une requête et demande au reste de l’application d’effectuer l’opération.

Dans Slim, une `Action` joue généralement ce rôle. Elle :

1. lit l’identifiant présent dans l’URL ;
2. appelle le service applicatif ;
3. transforme le résultat en JSON ;
4. choisit le statut HTTP.

## Exemple de circulation MVC

```text
Client
  ↓ requête HTTP
Contrôleur / Action Slim
  ↓ appel du service
Modèle et règles métier
  ↓ résultat
Contrôleur / Action Slim
  ↓ réponse JSON
Client
```

MVC est utile, mais il reste assez général. Il n’explique pas précisément comment isoler la base de données du métier. L’architecture hexagonale complète cette séparation.

# 6. L’architecture hexagonale

## L’idée principale

L’architecture hexagonale place les règles importantes au centre de l’application et les technologies à l’extérieur.

Le centre ne doit pas savoir si la demande provient de Slim, d’une commande en console ou d’un test. Il ne doit pas non plus savoir si les données sont conservées dans PostgreSQL, dans un fichier ou dans un tableau en mémoire.

```text
Adaptateur d’entrée
Action Slim
      ↓
Port d’entrée
ServiceRendezVousInterface
      ↓
Service applicatif
ServiceRendezVous
      ↓
Domaine
RendezVous
      ↓
Port de sortie
RendezVousRepositoryInterface
      ↓
Adaptateur de sortie
RendezVousRepository avec PDO
      ↓
PostgreSQL
```

## Le domaine

Le domaine représente les concepts et règles du problème réel.

Pour Toubilib, il contient notamment :

- l’entité `RendezVous` ;
- l’énumération `EtatRendezVous` ;
- les exceptions correspondant aux transitions interdites.

Le domaine ne doit pas dépendre de Slim, de PDO, de PostgreSQL ou du format JSON.

## La couche application

La couche application organise les cas d’utilisation.

Un cas d’utilisation est une action proposée par le logiciel, par exemple « annuler un rendez-vous ».

`ServiceRendezVous` coordonne ce cas d’utilisation :

1. rechercher le rendez-vous ;
2. vérifier qu’il existe ;
3. demander à l’entité de s’annuler ;
4. sauvegarder la modification ;
5. retourner un DTO.

Le service ne contient pas la requête SQL et ne doit pas reproduire les règles internes de l’entité.

## Les ports

Un port est une interface placée à la frontière du noyau applicatif.

Le port d’entrée indique ce que l’application permet de faire :

```php
ServiceRendezVousInterface::annuler(string $id)
```

Le port de sortie indique ce dont l’application a besoin pour fonctionner :

```php
RendezVousRepositoryInterface::findById(string $id)
RendezVousRepositoryInterface::save(RendezVous $rendezVous)
```

## Les adaptateurs

Un adaptateur relie une technologie extérieure à un port.

L’action Slim est un adaptateur d’entrée : elle adapte une requête HTTP en appel de méthode PHP.

Le repository PDO est un adaptateur de sortie : il adapte des appels PHP en requêtes SQL PostgreSQL.

# 7. L’inversion de dépendances

Sans inversion de dépendances, le service dépendrait directement d’une classe utilisant PDO :

```text
ServiceRendezVous → RendezVousRepository PDO → PostgreSQL
```

Le métier serait alors lié à PostgreSQL. Il deviendrait plus difficile à tester et à réutiliser.

Avec l’inversion de dépendances, le noyau définit l’interface dont il a besoin :

```text
ServiceRendezVous → RendezVousRepositoryInterface ← RendezVousRepository PDO
```

La flèche de dépendance de l’adaptateur est dirigée vers l’interface du noyau. L’adaptateur de persistance respecte le contrat défini par l’application.

Le service reçoit cette interface dans son constructeur :

```php
public function __construct(
    private readonly RendezVousRepositoryInterface $repository,
) {
}
```

Il ne sait donc pas quelle classe concrète sera utilisée. On peut lui fournir :

- le repository PostgreSQL dans l’application réelle ;
- un faux repository en mémoire dans les tests.

C’est le principe d’injection de dépendances.

# 8. Le rôle du DTO

DTO signifie Data Transfer Object, c’est-à-dire objet de transfert de données.

`RendezVousDTO` transporte les informations que le service souhaite retourner. Il ne contient ni règle métier, ni accès à la base de données.

Il évite de retourner directement l’entité métier à l’extérieur. Cette séparation permet :

- de contrôler les informations exposées ;
- de choisir un format adapté à l’API ;
- d’éviter que le code Web modifie l’entité ;
- de faire évoluer la représentation JSON sans casser le domaine.

La méthode `fromEntity()` copie les informations utiles :

```php
$dto = RendezVousDTO::fromEntity($rendezVous);
```

La méthode `jsonSerialize()` indique la structure du futur JSON.

# 9. Les dossiers du projet Toubilib

## Le dossier `app`

Ce dossier contient l’application PHP.

## Le dossier `app/public`

Il contient le point d’entrée Web `index.php`. Toutes les requêtes HTTP destinées à Slim arrivent par ce fichier.

Son rôle est de charger Composer, construire l’application Slim, puis la démarrer.

## Le dossier `app/config`

Il doit contenir la configuration technique :

- création de l’application Slim ;
- déclaration des routes ;
- configuration du conteneur d’injection de dépendances ;
- paramètres de connexion à la base.

Ce dossier relie les interfaces aux implantations concrètes.

## Le dossier `app/src/domain`

Il contient le cœur métier.

```text
domain/
├── entities/
│   ├── RendezVous.php
│   └── EtatRendezVous.php
└── exceptions/
    └── TransitionEtatRendezVousException.php
```

## Le dossier `app/src/application`

Il contient les cas d’utilisation et les contrats du noyau.

```text
application/
├── dto/
│   └── RendezVousDTO.php
├── exceptions/
│   └── RendezVousNotFoundException.php
├── ports/
│   ├── api/
│   │   └── ServiceRendezVousInterface.php
│   └── spi/
│       └── RendezVousRepositoryInterface.php
└── usecases/
    └── ServiceRendezVous.php
```

`api` désigne les ports d’entrée proposés par l’application. `spi` désigne les ports utilisés par l’application pour communiquer avec des services extérieurs, comme la persistance.

## Le dossier `app/src/adapters`

Il contient les éléments liés aux technologies externes.

Il accueillera notamment :

- l’action Slim d’annulation dans les adaptateurs d’entrée ;
- le repository PDO dans les adaptateurs de sortie.

## Le dossier `app/sql`

Il contient :

- `toubilib.schema.sql`, qui définit les tables ;
- `toubilib.data.sql`, qui fournit les données initiales.

## Le dossier `app/tests`

Il contient les tests automatisés du domaine, des services et éventuellement des adaptateurs.

# 10. Le namespace

Un namespace est le nom complet d’une classe. Il évite les conflits de noms et représente son emplacement logique.

Exemple :

```php
namespace toubilib\domain\entities;
```

Le nom complet de la classe est alors :

```text
toubilib\domain\entities\RendezVous
```

Pour utiliser cette classe depuis un autre namespace :

```php
use toubilib\domain\entities\RendezVous;
```

Le namespace ne réalise aucun traitement. Il sert à organiser et identifier les classes.

# 11. Déroulement complet de l’annulation

Supposons que le client envoie :

```http
PATCH /rendez-vous/rdv-123/annulation
```

## Étape 1 : Slim choisit la route

Slim compare la méthode et l’URL avec les routes configurées. Il sélectionne l’action d’annulation.

## Étape 2 : l’action lit la requête

L’action récupère `rdv-123` dans l’URL et appelle le port d’entrée :

```php
$dto = $service->annuler('rdv-123');
```

## Étape 3 : le service recherche le rendez-vous

Le service utilise le port de sortie :

```php
$rendezVous = $repository->findById($id);
```

## Étape 4 : le service vérifie son existence

```php
if ($rendezVous === null) {
    throw new RendezVousNotFoundException($id);
}
```

Cette vérification doit être effectuée avant d’appeler une méthode sur le résultat. Il est impossible d’appeler `annuler()` sur `null`.

## Étape 5 : l’entité applique la règle métier

```php
$rendezVous->annuler();
```

L’entité vérifie :

- que son état est `PLANIFIE` ;
- que sa date de début n’est pas passée.

Si la transition est valide, l’état devient `ANNULE`. Sinon, une exception métier est déclenchée.

## Étape 6 : le service demande la sauvegarde

```php
$repository->save($rendezVous);
```

Le repository PDO exécutera une requête similaire à :

```sql
UPDATE rdv
SET status = :status
WHERE id = :id;
```

## Étape 7 : le service crée le DTO

```php
return RendezVousDTO::fromEntity($rendezVous);
```

## Étape 8 : l’action retourne du JSON

L’action encode le DTO et renvoie une réponse `200 OK`.

Si une exception est déclenchée, l’action ou un middleware la transforme en réponse adaptée :

- rendez-vous inexistant : `404 Not Found` ;
- annulation interdite : `409 Conflict` ;
- problème de base de données : `500 Internal Server Error`.

# 12. Pourquoi chaque règle se trouve à un endroit précis

## Dans l’entité

Les règles qui définissent ce qu’est un rendez-vous valide appartiennent au domaine.

Exemple : un rendez-vous passé ne peut pas être annulé.

Cette règle doit rester vraie, que la demande provienne d’une API, d’un test ou d’une commande en console.

## Dans le service

Le service coordonne le scénario complet.

Exemple : rechercher, vérifier l’existence, annuler, sauvegarder et fabriquer le DTO.

## Dans le repository

Le repository contient la logique de persistance.

Exemple : exécuter `SELECT` et `UPDATE`, puis transformer une ligne SQL en entité.

## Dans l’action Slim

L’action contient la logique HTTP.

Exemple : lire un paramètre d’URL et choisir les codes `200`, `404` ou `409`.

## Dans le DTO

Le DTO contient uniquement les données à transférer vers l’extérieur.

# 13. Les erreurs importantes

## Identifiant absent ou invalide

L’action ou un validateur peut refuser une URL ou un identifiant mal formé. La réponse peut être `400 Bad Request`.

## Rendez-vous inexistant

Le repository retourne `null`. Le service déclenche `RendezVousNotFoundException`. La couche HTTP traduit cette erreur en `404 Not Found`.

## Rendez-vous passé

L’entité refuse l’annulation et déclenche `TransitionEtatRendezVousException`. La couche HTTP peut retourner `409 Conflict`.

## Rendez-vous déjà annulé ou terminé

L’entité refuse également la transition, car seul un rendez-vous `PLANIFIE` peut être annulé.

## Erreur PostgreSQL

Le repository reçoit une erreur PDO. Il doit la transformer en exception de persistance ou la laisser remonter vers le gestionnaire d’erreurs. La réponse HTTP est généralement `500 Internal Server Error`.

# 14. Pourquoi écrire des tests

Les tests vérifient les règles sans devoir lancer manuellement l’application et modifier la vraie base de données.

## Test du domaine

Un test du domaine crée directement une entité, appelle une méthode et vérifie son nouvel état ou l’exception obtenue.

Le test n’a besoin ni de Slim ni de PostgreSQL.

## Test du service

Un test du service utilise un faux repository en mémoire.

Il peut vérifier que :

- le service trouve l’entité ;
- une absence produit la bonne exception ;
- l’entité modifiée est sauvegardée ;
- le DTO retourné contient l’état `ANNULE`.

## Test de l’adaptateur

Un test d’intégration du repository vérifie les véritables requêtes PDO avec une base réservée aux tests.

## Test HTTP

Un test HTTP envoie une requête à l’application Slim et vérifie le statut et le JSON retournés.

# 15. Ce qui a été réalisé jusqu’à présent

La partie codée contient actuellement :

- `EtatRendezVous`, qui définit les états possibles ;
- `RendezVous`, qui contient les données et règles métier ;
- `TransitionEtatRendezVousException`, pour les transitions interdites ;
- `ServiceRendezVousInterface`, le port d’entrée ;
- `ServiceRendezVous`, qui coordonne l’annulation ;
- `RendezVousNotFoundException`, pour un identifiant inexistant ;
- `RendezVousDTO`, qui prépare le résultat retourné.

Le repository, les tests, l’action Slim, la route et la configuration des dépendances ne sont pas encore réalisés.

# 16. Questions et exercices pour chaque partie

Les corrections ne sont volontairement pas placées juste après les questions. Il faut répondre sans relire la partie correspondante, puis vérifier ses réponses dans le cours.

## Partie 1 — But général de Toubilib

### Questions de compréhension

1. Quelle fonctionnalité est développée dans ce TD ?
2. Pourquoi une annulation ne se résume-t-elle pas à une requête SQL ?
3. Quelles vérifications faut-il effectuer avant de modifier la base ?

### Exercice de rédaction

Explique en cinq lignes le parcours d’une demande d’annulation, depuis le client jusqu’à PostgreSQL.

## Partie 2 — Technologies utilisées

### Questions de compréhension

1. Quelle différence existe-t-il entre PHP, Slim et Composer ?
2. Quel est le rôle de PostgreSQL ?
3. À quoi sert PDO ?
4. Pourquoi le navigateur ne voit-il jamais le code PHP ?
5. Quels sont les trois services Docker du projet ?

### Exercice de code

Écris une instruction PHP qui crée une date immuable correspondant au 20 décembre 2030 à 10 heures.

```php
// Ta réponse :
```

### Exercice pratique

Dans `composer.json`, retrouve la ligne qui associe le namespace `toubilib\` au dossier `src`. Explique ensuite comment Composer retrouve le fichier de la classe `toubilib\domain\entities\RendezVous`.

## Partie 3 — Fonctionnement d’une application Web

### Questions de compréhension

1. Quelles informations importantes contient une requête HTTP ?
2. Quelle est la différence entre un code `200`, `404` et `500` ?
3. Pourquoi la méthode HTTP fait-elle partie du sens d’une route REST ?

### Exercice de rédaction

Écris une requête HTTP permettant d’annuler le rendez-vous `abc-123`, puis indique le statut attendu dans chacun des cas suivants : succès, rendez-vous absent et transition interdite.

## Partie 4 — PHP orienté objet

### Questions de compréhension

1. Quelle est la différence entre une classe et un objet ?
2. Pourquoi les propriétés de `RendezVous` sont-elles privées ?
3. À quoi sert un constructeur ?
4. Quelle différence existe-t-il entre une interface et une classe ?
5. Pourquoi utiliser une énumération pour l’état ?
6. Que représente une exception ?

### Exercice de code

Crée un rendez-vous futur avec l’identifiant `rdv-test`, puis appelle sa méthode `annuler()`.

```php
// Ta réponse :
```

### Exercice de code

Écris une énumération `ModeConsultation` possédant les cas `CABINET` et `VIDEO`.

```php
// Ta réponse :
```

## Partie 5 — MVC

### Questions de compréhension

1. Que signifient les lettres M, V et C ?
2. Quel composant reçoit la requête HTTP ?
3. Dans une API sans page HTML, sous quelle forme le résultat est-il généralement présenté ?
4. Pourquoi le contrôleur ne doit-il pas exécuter lui-même toutes les règles métier et requêtes SQL ?

### Exercice de classement

Classe chaque élément dans Modèle, Vue ou Contrôleur :

- l’entité `RendezVous` ;
- une action Slim ;
- la réponse JSON ;
- la règle interdisant l’annulation d’un rendez-vous passé.

### Exercice de rédaction

Explique avec tes mots le trajet d’une requête dans MVC en utilisant les mots client, contrôleur, modèle et réponse.

## Partie 6 — Architecture hexagonale

### Questions de compréhension

1. Qu’est-ce qui est placé au centre de l’architecture ?
2. Quelle différence existe-t-il entre un port et un adaptateur ?
3. Quelle différence existe-t-il entre un adaptateur d’entrée et un adaptateur de sortie ?
4. Pourquoi le domaine ne doit-il pas importer Slim ou PDO ?
5. Quel est le rôle précis de `ServiceRendezVous` ?

### Exercice de classement

Classe les composants suivants dans la bonne catégorie : domaine, application, port d’entrée, port de sortie, adaptateur d’entrée ou adaptateur de sortie.

- `RendezVous` ;
- `ServiceRendezVous` ;
- `ServiceRendezVousInterface` ;
- `RendezVousRepositoryInterface` ;
- action Slim d’annulation ;
- repository PDO.

### Exercice de dessin

Sans regarder le cours, dessine les composants traversés par une annulation. Les flèches doivent partir de la requête HTTP et aller jusqu’à PostgreSQL.

## Partie 7 — Inversion de dépendances

### Questions de compréhension

1. Pourquoi le service ne dépend-il pas directement de la classe PDO ?
2. Qui définit `RendezVousRepositoryInterface` ?
3. Qui implémente cette interface ?
4. Qu’est-ce que l’injection de dépendances ?
5. Quel avantage cette organisation apporte-t-elle aux tests ?

### Exercice de code

Écris uniquement le constructeur d’un service qui reçoit un objet respectant `RendezVousRepositoryInterface`.

```php
// Ta réponse :
```

### Exercice de réflexion

On remplace PostgreSQL par un stockage en mémoire. Quelles classes devraient changer et quelles classes devraient rester identiques ? Justifie ta réponse.

## Partie 8 — DTO

### Questions de compréhension

1. Que signifie DTO ?
2. Pourquoi ne retourne-t-on pas directement l’entité `RendezVous` ?
3. Un DTO doit-il contenir la règle empêchant l’annulation d’un rendez-vous passé ?
4. Quel est le rôle de `fromEntity()` ?
5. À quoi sert `jsonSerialize()` ?

### Exercice de code

Écris un DTO minimal `PatientDTO` contenant un identifiant, un nom et un prénom. Ses propriétés doivent être publiques, typées et non modifiables.

```php
// Ta réponse :
```

## Partie 9 — Dossiers du projet

### Questions de compréhension

1. Dans quel dossier place-t-on les entités ?
2. Dans quel dossier place-t-on les ports d’entrée ?
3. Dans quel dossier placera-t-on le repository PDO ?
4. Quel est le rôle de `public/index.php` ?
5. Quelle différence existe-t-il entre les fichiers SQL de schéma et de données ?
6. Où doit être déclarée la route Slim ?

### Exercice pratique

Complète les chemins suivants :

```text
Classe RendezVous : app/src/____________________________
ServiceRendezVousInterface : app/src/___________________
RendezVousDTO : app/src/________________________________
Tests du domaine : app/tests/___________________________
```

## Partie 10 — Namespaces

### Questions de compréhension

1. À quoi sert un namespace ?
2. Quel lien existe-t-il entre namespace, autoloading PSR-4 et chemin du fichier ?
3. À quoi sert le mot-clé `use` ?
4. Le namespace exécute-t-il une opération métier ?

### Exercice de code

Écris le début du fichier `Patient.php`, placé dans `src/domain/entities`, avec la déclaration stricte, le namespace et une classe finale vide.

```php
// Ta réponse :
```

## Partie 11 — Déroulement complet de l’annulation

### Questions de compréhension

1. Replace dans l’ordre : créer le DTO, rechercher le rendez-vous, sauvegarder, vérifier l’existence, annuler l’entité.
2. Que renvoie `findById()` lorsque le rendez-vous n’existe pas ?
3. Pourquoi faut-il vérifier le résultat avant d’appeler `annuler()` ?
4. Quel composant déclenche la sauvegarde et quel composant exécute réellement le SQL ?
5. À quel moment l’entité passe-t-elle à l’état `ANNULE` ?

### Exercice de code

Réécris de mémoire le corps de `ServiceRendezVous::annuler()`. Il doit rechercher, vérifier, annuler, sauvegarder et retourner un DTO.

```php
// Ta réponse :
```

## Partie 12 — Placement des responsabilités

### Questions de compréhension

Indique le composant responsable de chaque opération :

1. vérifier que la date du rendez-vous n’est pas passée ;
2. exécuter un `UPDATE` SQL ;
3. choisir le statut HTTP ;
4. définir les propriétés exposées en JSON ;
5. coordonner la recherche et la sauvegarde ;
6. choisir quelle classe concrète correspond à une interface.

### Exercice de réflexion

Un développeur place la règle « un rendez-vous passé ne peut pas être annulé » dans l’action Slim. Explique le problème et indique où déplacer cette règle.

## Partie 13 — Gestion des erreurs

### Questions de compréhension

1. Quelle exception correspond à un rendez-vous inexistant ?
2. Quelle exception correspond à un changement d’état interdit ?
3. Quelle différence existe-t-il entre une erreur métier et une erreur technique ?
4. Quel statut HTTP associer à une absence, un conflit métier et une erreur de base de données ?

### Exercice de code

Écris le test conditionnel qui vérifie si `$rendezVous` vaut `null` et déclenche `RendezVousNotFoundException` avec l’identifiant `$id`.

```php
// Ta réponse :
```

### Exercice de correspondance

Associe chaque situation à `400`, `404`, `409` ou `500` :

- identifiant mal formé ;
- rendez-vous absent ;
- rendez-vous déjà annulé ;
- connexion PostgreSQL impossible.

## Partie 14 — Tests automatisés

### Questions de compréhension

1. Quelle différence existe-t-il entre un test du domaine, un test du service et un test d’intégration du repository ?
2. Pourquoi un test du domaine n’a-t-il pas besoin de Slim ?
3. Qu’est-ce qu’un faux repository en mémoire ?
4. Pourquoi ne faut-il pas dépendre des données de la vraie base dans tous les tests ?

### Exercice de code

Écris en pseudo-code ou avec Pest un test vérifiant qu’un rendez-vous futur et planifié devient `ANNULE`.

```php
// Ta réponse :
```

### Exercice de code

Écris en pseudo-code ou avec Pest un test vérifiant que l’annulation d’un rendez-vous passé déclenche `TransitionEtatRendezVousException`.

```php
// Ta réponse :
```

## Partie 15 — État actuel du projet

### Questions de compréhension

1. Quels composants sont déjà programmés ?
2. Quels composants manquent encore pour obtenir une fonctionnalité HTTP complète ?
3. Pourquoi le service ne peut-il pas encore fonctionner réellement avec PostgreSQL ?
4. Pourquoi l’API d’annulation n’est-elle pas encore accessible depuis une URL ?

### Exercice de planification

Replace les prochaines réalisations dans un ordre logique :

- action Slim ;
- tests du domaine ;
- configuration de l’injection de dépendances ;
- port de sortie ;
- route HTTP ;
- repository PDO.

# 17. Évaluation finale sans le cours

## Question orale

Explique toute l’architecture de Toubilib en deux minutes comme si tu la présentais à un professeur. Utilise obligatoirement les mots domaine, service, port, adaptateur, DTO, Slim, PDO et PostgreSQL.

## Exercice de parcours

Pour une annulation réussie, donne dans l’ordre :

1. la méthode HTTP et l’URL ;
2. l’action appelée ;
3. le port d’entrée utilisé ;
4. le service exécuté ;
5. le port de sortie utilisé ;
6. l’adaptateur de persistance utilisé ;
7. la règle appliquée par l’entité ;
8. le DTO retourné ;
9. le statut HTTP attendu.

## Exercice de diagnostic

Pour chaque erreur suivante, indique la couche fautive et propose une correction :

1. l’action exécute directement une requête SQL ;
2. le repository décide si un rendez-vous passé peut être annulé ;
3. le DTO modifie l’état de l’entité ;
4. le domaine importe une classe Slim ;
5. le service retourne directement une réponse HTTP.
