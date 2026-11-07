<?php

declare(strict_types=1);

namespace toubilib\application\dto;

use JsonSerializable;
use toubilib\domain\entities\RendezVous;

final readonly class RendezVousDTO implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $praticienId,
        public ?string $patientId,
        public string $dateHeureDebut,
        public ?string $dateHeureFin,
        public int $duree,
        public string $etat,
        public ?string $motifVisite,
    ) {
    }

    public static function fromEntity(RendezVous $rendezVous): self
    {
        return new self(
            id: $rendezVous->getId(),
            praticienId: $rendezVous->getPraticienId(),
            patientId: $rendezVous->getPatientId(),
            dateHeureDebut: $rendezVous->getDateHeureDebut()->format(DATE_ATOM),
            dateHeureFin: $rendezVous->getDateHeureFin()?->format(DATE_ATOM),
            duree: $rendezVous->getDuree(),
            etat: $rendezVous->getEtat()->name,
            motifVisite: $rendezVous->getMotifVisite(),
        );
    }

    /**
     * @return array<string, int|string|null>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'praticien_id' => $this->praticienId,
            'patient_id' => $this->patientId,
            'date_heure_debut' => $this->dateHeureDebut,
            'date_heure_fin' => $this->dateHeureFin,
            'duree' => $this->duree,
            'etat' => $this->etat,
            'motif_visite' => $this->motifVisite,
        ];
    }
}
