<?php

declare(strict_types=1);

namespace toubilib\domain\entities;

use DateTimeImmutable;
use DateTimeInterface;
use toubilib\domain\exceptions\TransitionEtatRendezVousException;

final class RendezVous
{
    public function __construct(
        private readonly string $id,
        private readonly string $praticienId,
        private readonly string $patientId,
        private readonly DateTimeImmutable $dateHeureDebut,
        private readonly int $duree,
        private readonly ?DateTimeImmutable $dateHeureFin = null,
        private readonly ?DateTimeImmutable $dateCreation = null,
        private readonly ?string $motifVisite = null,
        private EtatRendezVous $etat = EtatRendezVous::PLANIFIE,
    ) {
    }

    public function annuler(?DateTimeInterface $maintenant = null): void
    {
        $maintenant ??= new DateTimeImmutable();

        if ($this->etat !== EtatRendezVous::PLANIFIE) {
            throw new TransitionEtatRendezVousException(
                'Seul un rendez-vous planifie peut etre annule.',
            );
        }

        if ($this->dateHeureDebut <= $maintenant) {
            throw new TransitionEtatRendezVousException(
                'Un rendez-vous passe ne peut pas etre annule.',
            );
        }

        $this->etat = EtatRendezVous::ANNULE;
    }

    public function honorer(): void
    {
        $this->terminer(EtatRendezVous::HONORE);
    }

    public function marquerNonHonore(): void
    {
        $this->terminer(EtatRendezVous::NON_HONORE);
    }

    private function terminer(EtatRendezVous $nouvelEtat): void
    {
        if ($this->etat !== EtatRendezVous::PLANIFIE) {
            throw new TransitionEtatRendezVousException(
                'Seul un rendez-vous planifie peut etre termine.',
            );
        }

        $this->etat = $nouvelEtat;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPraticienId(): string
    {
        return $this->praticienId;
    }

    public function getPatientId(): string
    {
        return $this->patientId;
    }

    public function getDateHeureDebut(): DateTimeImmutable
    {
        return $this->dateHeureDebut;
    }

    public function getDuree(): int
    {
        return $this->duree;
    }

    public function getDateHeureFin(): ?DateTimeImmutable
    {
        return $this->dateHeureFin;
    }

    public function getDateCreation(): ?DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function getMotifVisite(): ?string
    {
        return $this->motifVisite;
    }

    public function getEtat(): EtatRendezVous
    {
        return $this->etat;
    }
}
