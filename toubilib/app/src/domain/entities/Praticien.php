<?php

declare(strict_types=1);

namespace toubilib\domain\entities;

use DateTimeImmutable;
use toubilib\domain\exceptions\CreneauRendezVousIndisponibleException;
use toubilib\domain\exceptions\DateRendezVousInvalideException;
use toubilib\domain\exceptions\MotifRendezVousInvalideException;

final class Praticien
{
    /** @var list<RendezVous> */
    private array $rendezVous = [];

    public function __construct(
        private readonly string $id,
        private readonly string $nom,
        private readonly string $prenom,
        private readonly string $ville,
        private readonly string $email,
        private readonly string $telephone,
        private readonly Specialite $specialite,
        private readonly ?string $structureId = null,
        private readonly ?string $rppsId = null,
        private readonly bool $organisation = false,
        private readonly bool $accepteNouveauPatient = true,
        private readonly string $titre = 'Dr.',
        /** @var array<string, int> */
        private readonly array $dureesParMotif = ['CI' => 30, 'C0' => 20, 'CS' => 15],
        /** @var list<int> */
        private readonly array $joursOuvrables = [1, 2, 3, 4, 5],
        private readonly string $heureOuverture = '08:00',
        private readonly string $heureFermeture = '18:00',
    ) {
    }

    public function getId(): string { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function getPrenom(): string { return $this->prenom; }
    public function getVille(): string { return $this->ville; }
    public function getEmail(): string { return $this->email; }
    public function getTelephone(): string { return $this->telephone; }
    public function getSpecialite(): Specialite { return $this->specialite; }
    public function getStructureId(): ?string { return $this->structureId; }
    public function getRppsId(): ?string { return $this->rppsId; }
    public function estUneOrganisation(): bool { return $this->organisation; }
    public function accepteNouveauPatient(): bool { return $this->accepteNouveauPatient; }
    public function getTitre(): string { return $this->titre; }

    public function verifierMotif(string $motif): void
    {
        if (!array_key_exists($motif, $this->dureesParMotif)) {
            throw new MotifRendezVousInvalideException("Motif de rendez-vous invalide : {$motif}");
        }
    }

    public function calculerDuree(string $motif): int
    {
        $this->verifierMotif($motif);

        return $this->dureesParMotif[$motif];
    }

    public function verifierDateRendezVous(
        DateTimeImmutable $dateHeureDebut,
        int $duree,
        ?DateTimeImmutable $maintenant = null,
    ): void {
        $maintenant ??= new DateTimeImmutable();
        $dateHeureFin = $dateHeureDebut->modify("+{$duree} minutes");

        if ($dateHeureDebut <= $maintenant) {
            throw new DateRendezVousInvalideException('La date du rendez-vous doit être future.');
        }

        if (!in_array((int) $dateHeureDebut->format('N'), $this->joursOuvrables, true)) {
            throw new DateRendezVousInvalideException('Le rendez-vous doit être fixé un jour ouvrable.');
        }

        if (
            $dateHeureDebut->format('H:i') < $this->heureOuverture
            || $dateHeureFin->format('Y-m-d') !== $dateHeureDebut->format('Y-m-d')
            || $dateHeureFin->format('H:i') > $this->heureFermeture
        ) {
            throw new DateRendezVousInvalideException('Le rendez-vous est en dehors des horaires acceptés.');
        }
    }

    /**
     * Débuts de créneaux libres pour un motif sur une journée (pas = durée du motif).
     * Suppose les rendez-vous du jour chargés (chargerRendezVous).
     *
     * @return list<DateTimeImmutable>
     */
    public function creneauxDisponibles(
        DateTimeImmutable $jour,
        string $motif,
        ?DateTimeImmutable $maintenant = null,
    ): array {
        $duree = $this->calculerDuree($motif);
        [$h, $m] = array_map('intval', explode(':', $this->heureOuverture));
        $libres = [];

        for ($debut = $jour->setTime($h, $m); $debut->format('Y-m-d') === $jour->format('Y-m-d'); $debut = $debut->modify("+{$duree} minutes")) {
            try {
                $this->verifierDateRendezVous($debut, $duree, $maintenant);
                $this->verifierDisponibilite(
                    new RendezVous('dispo', $this->id, 'dispo', $debut, $duree, $debut->modify("+{$duree} minutes")),
                );
                $libres[] = $debut;
            } catch (\DomainException) {
                // créneau hors horaires, passé ou déjà occupé
            }
        }

        return $libres;
    }

    /** Bloque une demi-journée ("matin" jusqu'à 12h, "apres-midi" à partir de 12h). */
    public function creerIndisponibilite(
        DateTimeImmutable $jour,
        string $periode,
        ?DateTimeImmutable $maintenant = null,
    ): RendezVous {
        [$de, $a] = match ($periode) {
            'matin' => [$this->heureOuverture, '12:00'],
            'apres-midi' => ['12:00', $this->heureFermeture],
            default => throw new DateRendezVousInvalideException('Période invalide : matin ou apres-midi.'),
        };
        $date = $jour->format('Y-m-d');
        $debut = new DateTimeImmutable("{$date} {$de}");
        $duree = intdiv((new DateTimeImmutable("{$date} {$a}"))->getTimestamp() - $debut->getTimestamp(), 60);

        $this->verifierDateRendezVous($debut, $duree, $maintenant);
        $indisponibilite = RendezVous::indisponibilite($this->id, $debut, $duree);
        $this->verifierDisponibilite($indisponibilite);

        return $indisponibilite;
    }

    /** @param list<RendezVous> $rendezVous */
    public function chargerRendezVous(array $rendezVous): void
    {
        foreach ($rendezVous as $rendezVousExistant) {
            if ($rendezVousExistant->getPraticienId() !== $this->id) {
                throw new \InvalidArgumentException('Le rendez-vous appartient à un autre praticien.');
            }
        }

        $this->rendezVous = array_values($rendezVous);
    }

    public function verifierDisponibilite(RendezVous $demande): void
    {
        foreach ($this->rendezVous as $rendezVousExistant) {
            if (
                $rendezVousExistant->getEtat() !== EtatRendezVous::ANNULE
                && $demande->chevauche($rendezVousExistant)
            ) {
                throw new CreneauRendezVousIndisponibleException(
                    'Le praticien est indisponible sur le créneau demandé.',
                );
            }
        }
    }
}
