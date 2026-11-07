<?php

declare(strict_types=1);

namespace toubilib\adapters\repositories;

use DateTimeImmutable;
use PDO;
use RuntimeException;
use toubilib\application\ports\spi\RendezVousRepositoryInterface;
use toubilib\domain\entities\EtatRendezVous;
use toubilib\domain\entities\RendezVous;

final class RendezVousRepository implements RendezVousRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function findById(string $id): ?RendezVous
    {
        $statement = $this->pdo->prepare(
            'SELECT id, praticien_id, patient_id, date_heure_debut, duree, '
            . 'date_heure_fin, date_creation, motif_visite, status FROM rdv WHERE id = :id',
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function findByPraticienEtPeriode(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, praticien_id, patient_id, date_heure_debut, duree, '
            . 'date_heure_fin, date_creation, motif_visite, status FROM rdv '
            . 'WHERE praticien_id = :praticien AND status <> :annule '
            . 'AND date_heure_debut >= :debut AND date_heure_debut < :fin ORDER BY date_heure_debut',
        );
        $statement->execute([
            'praticien' => $praticienId,
            'annule' => EtatRendezVous::ANNULE->value,
            'debut' => $debut->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
        ]);

        return array_map($this->hydrate(...), $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): RendezVous
    {
        return new RendezVous(
            id: $row['id'],
            praticienId: $row['praticien_id'],
            patientId: $row['patient_id'],
            dateHeureDebut: new DateTimeImmutable($row['date_heure_debut']),
            duree: (int) $row['duree'],
            dateHeureFin: $row['date_heure_fin'] === null ? null : new DateTimeImmutable($row['date_heure_fin']),
            dateCreation: $row['date_creation'] === null ? null : new DateTimeImmutable($row['date_creation']),
            motifVisite: $row['motif_visite'],
            etat: EtatRendezVous::from((int) $row['status']),
        );
    }

    public function save(RendezVous $rendezVous): void
    {
        $statement = $this->pdo->prepare('UPDATE rdv SET status = :status WHERE id = :id');
        $statement->bindValue(':status', $rendezVous->getEtat()->value, PDO::PARAM_INT);
        $statement->bindValue(':id', $rendezVous->getId(), PDO::PARAM_STR);
        $statement->execute();

        if ($statement->rowCount() === 0) {
            throw new RuntimeException('Rendez-vous introuvable lors de la sauvegarde.');
        }
    }
}
