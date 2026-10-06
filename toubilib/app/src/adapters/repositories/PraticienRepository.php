<?php

declare(strict_types=1);

namespace toubilib\adapters\repositories;

use PDO;
use toubilib\application\ports\spi\PraticienRepositoryInterface;
use toubilib\domain\entities\Praticien;
use toubilib\domain\entities\Specialite;

final class PraticienRepository implements PraticienRepositoryInterface
{
    private const SELECT = 'SELECT p.id, p.nom, p.prenom, p.ville, p.email, p.telephone, '
        . 'p.structure_id, p.rpps_id, p.organisation, p.nouveau_patient, p.titre, '
        . 's.id AS specialite_id, s.libelle AS specialite_libelle, '
        . 's.description AS specialite_description '
        . 'FROM praticien p JOIN specialite s ON s.id = p.specialite_id';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $statement = $this->pdo->query(self::SELECT . ' ORDER BY p.nom, p.prenom');

        return array_map($this->hydrate(...), $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findById(string $id): ?Praticien
    {
        $statement = $this->pdo->prepare(self::SELECT . ' WHERE p.id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Praticien
    {
        return new Praticien(
            id: (string) $row['id'],
            nom: (string) $row['nom'],
            prenom: (string) $row['prenom'],
            ville: (string) $row['ville'],
            email: (string) $row['email'],
            telephone: (string) $row['telephone'],
            specialite: new Specialite(
                (int) $row['specialite_id'],
                (string) $row['specialite_libelle'],
                $row['specialite_description'] === null ? null : (string) $row['specialite_description'],
            ),
            structureId: $row['structure_id'] === null ? null : (string) $row['structure_id'],
            rppsId: $row['rpps_id'] === null ? null : (string) $row['rpps_id'],
            organisation: $this->toBool($row['organisation']),
            accepteNouveauPatient: $this->toBool($row['nouveau_patient']),
            titre: (string) $row['titre'],
        );
    }

    private function toBool(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 't';
    }
}
