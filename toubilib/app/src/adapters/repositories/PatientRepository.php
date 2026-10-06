<?php

declare(strict_types=1);

namespace toubilib\adapters\repositories;

use DateTimeImmutable;
use PDO;
use toubilib\application\ports\spi\PatientRepositoryInterface;
use toubilib\domain\entities\Patient;

final class PatientRepository implements PatientRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(string $id): ?Patient
    {
        $statement = $this->pdo->prepare(
            'SELECT id, nom, prenom, date_naissance, adresse, code_postal, ville, email, telephone '
            . 'FROM patient WHERE id = :id',
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new Patient(
            id: (string) $row['id'],
            nom: (string) $row['nom'],
            prenom: (string) $row['prenom'],
            dateNaissance: $row['date_naissance'] === null ? null : new DateTimeImmutable($row['date_naissance']),
            adresse: $row['adresse'] === null ? null : (string) $row['adresse'],
            codePostal: $row['code_postal'] === null ? null : (string) $row['code_postal'],
            ville: $row['ville'] === null ? null : (string) $row['ville'],
            email: $row['email'] === null ? null : (string) $row['email'],
            telephone: (string) $row['telephone'],
        );
    }
}
