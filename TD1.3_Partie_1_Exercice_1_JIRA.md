# TD1.3 — Partie 1 — Exercice 1 : création d’une UserStory dans JIRA

## 1. Données fournies par l’application cliente

L’application cliente doit transmettre dans le corps de la requête HTTP :

- `title` : le titre de la UserStory ;
- `description` : sa description ;
- `ownerId` : l’identifiant de son responsable.

## 2. Données reçues et retournées par le service

La méthode du port d’entrée utilisée est :

```php
public function createUserStory(CreateUserStoryDTO $dto): UserStoryOutputDTO;
```

Le service reçoit donc un `CreateUserStoryDTO` et retourne un `UserStoryOutputDTO`.

## 3. Vérification des données de la requête HTTP

Dans `CreateUserStoryAction`, le corps de la requête est transmis à `CreateUserStoryDTO::fromArray()`.

Cette méthode :

- vérifie que les valeurs sont des chaînes ou des nombres convertibles en chaînes ;
- supprime les espaces inutiles avec `trim()` ;
- impose un titre compris entre 3 et 255 caractères ;
- impose une description d’au moins 10 caractères ;
- impose un `ownerId` non vide.

Si une donnée est absente, incorrecte ou du mauvais type, une `ValidationException` est déclenchée. L’action retourne alors une réponse HTTP `422 Unprocessable Entity` contenant les erreurs au format JSON.

## 4. DTO contenant des données manquantes

Non, pas par l’utilisation normale de la classe. Le constructeur de `CreateUserStoryDTO` est privé : il faut utiliser `fromArray()`, qui remplace une donnée absente par une chaîne vide puis déclenche une `ValidationException`.

## 5. DTO contenant un `ownerId` inexistant

Oui. `CreateUserStoryDTO::fromArray()` vérifie uniquement que `ownerId` est présent et non vide. Cette méthode ne consulte pas la base de données. Un DTO peut donc être créé avec un identifiant correctement formé qui ne correspond à aucun responsable existant.

## 6. Vérification de l’existence du responsable

Dans `UserStoryService::createUserStory()`, le DTO est transmis à `CreateUserStoryValidator::validate()`.

Le validateur utilise le port de sortie `OwnerRepository` et appelle `findById($dto->ownerId)`. Si aucun responsable n’est trouvé, il déclenche une `ValidationException`. Le service transforme cette erreur en `DomainException`, puis l’action retourne une réponse HTTP `422`.

## 7. Création d’une entité avec un `ownerId` invalide

Non, le service ne crée pas la nouvelle `UserStory` tant que le responsable n’a pas été retrouvé et validé. De plus, l’entité ne reçoit pas directement une chaîne `ownerId` : sa méthode `assignTo()` exige un objet `Owner`.

Le constructeur de `UserStory` permet cependant de créer temporairement une entité sans responsable. C’est donc le service et son validateur qui garantissent que l’entité sauvegardée possède bien un responsable existant.
