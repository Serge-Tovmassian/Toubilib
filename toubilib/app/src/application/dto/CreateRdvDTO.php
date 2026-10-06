<?php

declare(strict_types=1);

namespace toubilib\application\dto;

use DateTimeImmutable;
use JsonSerializable;

final readonly class CreateRdvDTO implements JsonSerializable
{
    public function __construct(
        public string $praticienId,
        public string $patientId,
        public DateTimeImmutable $dateHeureDebut,
        public string $motifVisite,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'praticien_id' => $this->praticienId,
            'patient_id' => $this->patientId,
            'date_heure_debut' => $this->dateHeureDebut->format(DATE_ATOM),
            'motif_visite' => $this->motifVisite,
        ];
    }
}
