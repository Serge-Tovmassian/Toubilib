<?php

declare(strict_types=1);

namespace toubilib\application\ports\spi;

use DateTimeImmutable;
use toubilib\domain\entities\RendezVous;

interface RendezVousRepositoryInterface
{
    public function findById(string $id): ?RendezVous;

    /** @return list<RendezVous> rendez-vous non annulés, triés par date */
    public function findByPraticienEtPeriode(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array;

    /** @return list<RendezVous> tous états confondus, du plus récent au plus ancien */
    public function findByPatient(string $patientId): array;

    public function insert(RendezVous $rendezVous): void;

    public function save(RendezVous $rendezVous): void;
}
