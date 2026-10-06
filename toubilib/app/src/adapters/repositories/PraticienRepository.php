<?php

declare(strict_types=1);

namespace toubilib\adapters\repositories;

use DateTimeImmutable;
use PDO;
use toubilib\application\ports\spi\PraticienRepositoryInterface;
use toubilib\domain\entities\Praticien;
use toubilib\domain\entities\EtatRendezVous;
use toubilib\domain\entities\RendezVous;
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

    public function findByIdWithRendezVousForDate(string $id, DateTimeImmutable $date): ?Praticien
    {
        $praticien = $this->findById($id);
        if ($praticien === null) {
            return null;
        }

        $debutJournee = $date->setTime(0, 0);
        $finJournee = $debutJournee->modify('+1 day');
        $statement = $this->pdo->prepare(
            'SELECT id, praticien_id, patient_id, date_heure_debut, duree, '
            . 'date_heure_fin, date_creation, motif_visite, status FROM rdv '
            . 'WHERE praticien_id = :praticien_id '
            . 'AND date_heure_debut >= :debut AND date_heure_debut < :fin',
        );
        $statement->execute([
            'praticien_id' => $id,
            'debut' => $debutJournee->format('Y-m-d H:i:s'),
            'fin' => $finJournee->format('Y-m-d H:i:s'),
        ]);

        $praticien->chargerRendezVous(array_map(
            $this->hydrateRendezVous(...),
            $statement->fetchAll(PDO::FETCH_ASSOC),
        ));

        return $praticien;
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

    /** @param array<string, mixed> $row */
    private function hydrateRendezVous(array $row): RendezVous
    {
        return new RendezVous(
            id: (string) $row['id'],
            praticienId: (string) $row['praticien_id'],
            patientId: (string) $row['patient_id'],
            dateHeureDebut: new DateTimeImmutable($row['date_heure_debut']),
            duree: (int) $row['duree'],
            dateHeureFin: $row['date_heure_fin'] === null ? null : new DateTimeImmutable($row['date_heure_fin']),
            dateCreation: $row['date_creation'] === null ? null : new DateTimeImmutable($row['date_creation']),
            motifVisite: $row['motif_visite'] === null ? null : (string) $row['motif_visite'],
            etat: EtatRendezVous::from((int) $row['status']),
        );
    }

    private function toBool(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 't';
    }
}
