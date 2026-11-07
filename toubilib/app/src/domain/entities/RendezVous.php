<?php

declare(strict_types=1);

namespace toubilib\domain\entities;

use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Uuid\Uuid;
use toubilib\domain\exceptions\TransitionEtatRendezVousException;

final class RendezVous
{
    public function __construct(
        private readonly string $id,
        private readonly string $praticienId,
        private readonly ?string $patientId,
        private readonly DateTimeImmutable $dateHeureDebut,
        private readonly int $duree,
        private readonly ?DateTimeImmutable $dateHeureFin = null,
        private readonly ?DateTimeImmutable $dateCreation = null,
        private readonly ?string $motifVisite = null,
        private EtatRendezVous $etat = EtatRendezVous::PLANIFIE,
    ) {
    }

    public static function creer(
        Praticien $praticien,
        Patient $patient,
        DateTimeImmutable $dateHeureDebut,
        string $motifVisite,
    ): self {
        $duree = $praticien->calculerDuree($motifVisite);
        $praticien->verifierDateRendezVous($dateHeureDebut, $duree);

        $rendezVous = new self(
            id: Uuid::uuid4()->toString(),
            praticienId: $praticien->getId(),
            patientId: $patient->getId(),
            dateHeureDebut: $dateHeureDebut,
            duree: $duree,
            dateHeureFin: $dateHeureDebut->modify("+{$duree} minutes"),
            dateCreation: new DateTimeImmutable(),
            motifVisite: $motifVisite,
        );

        $praticien->verifierDisponibilite($rendezVous);

        return $rendezVous;
    }

    public static function indisponibilite(string $praticienId, DateTimeImmutable $debut, int $duree): self
    {
        return new self(
            id: Uuid::uuid4()->toString(),
            praticienId: $praticienId,
            patientId: null,
            dateHeureDebut: $debut,
            duree: $duree,
            dateHeureFin: $debut->modify("+{$duree} minutes"),
            dateCreation: new DateTimeImmutable(),
            motifVisite: 'indisponible',
        );
    }

    public function chevauche(self $autre): bool
    {
        return $this->dateHeureDebut < $autre->dateHeureFinCalculee()
            && $this->dateHeureFinCalculee() > $autre->dateHeureDebut;
    }

    private function dateHeureFinCalculee(): DateTimeImmutable
    {
        return $this->dateHeureFin ?? $this->dateHeureDebut->modify("+{$this->duree} minutes");
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

    public function getPatientId(): ?string
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
