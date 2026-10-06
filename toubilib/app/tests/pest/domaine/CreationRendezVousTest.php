<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use toubilib\domain\entities\Patient;
use toubilib\domain\entities\Praticien;
use toubilib\domain\entities\RendezVous;
use toubilib\domain\entities\Specialite;
use toubilib\domain\exceptions\CreneauRendezVousIndisponibleException;
use toubilib\domain\exceptions\DateRendezVousInvalideException;
use toubilib\domain\exceptions\MotifRendezVousInvalideException;

final class CreationRendezVousTest extends TestCase
{
    private function praticien(): Praticien
    {
        return new Praticien(
            id: 'praticien-1',
            nom: 'Dupont',
            prenom: 'Alice',
            ville: 'Nancy',
            email: 'alice@example.test',
            telephone: '0102030405',
            specialite: new Specialite(1, 'Médecine générale'),
            dureesParMotif: ['Consultation' => 30, 'Suivi' => 20],
        );
    }

    private function rendezVous(string $id, string $debut, int $duree = 30): RendezVous
    {
        return new RendezVous(
            $id,
            'praticien-1',
            'patient-1',
            new DateTimeImmutable($debut),
            $duree,
        );
    }

    public function testCalculeLaDureeSelonLeMotif(): void
    {
        self::assertSame(30, $this->praticien()->calculerDuree('Consultation'));
        self::assertSame(20, $this->praticien()->calculerDuree('Suivi'));
    }

    public function testRefuseUnMotifInconnu(): void
    {
        $this->expectException(MotifRendezVousInvalideException::class);
        $this->praticien()->calculerDuree('Motif inconnu');
    }

    public function testRefuseUnRendezVousLeWeekEnd(): void
    {
        $this->expectException(DateRendezVousInvalideException::class);
        $this->praticien()->verifierDateRendezVous(
            new DateTimeImmutable('2030-12-22 10:00:00'),
            30,
            new DateTimeImmutable('2030-01-01'),
        );
    }

    public function testRefuseUnRendezVousQuiDepasseLaFermeture(): void
    {
        $this->expectException(DateRendezVousInvalideException::class);
        $this->praticien()->verifierDateRendezVous(
            new DateTimeImmutable('2030-12-20 17:45:00'),
            30,
            new DateTimeImmutable('2030-01-01'),
        );
    }

    public function testCreeUnRendezVousValide(): void
    {
        $rendezVous = RendezVous::creer(
            $this->praticien(),
            new Patient('patient-1', 'Martin', 'Bob'),
            new DateTimeImmutable('2030-12-20 10:00:00'),
            'Suivi',
        );

        self::assertSame(20, $rendezVous->getDuree());
        self::assertSame('patient-1', $rendezVous->getPatientId());
        self::assertSame('2030-12-20 10:20', $rendezVous->getDateHeureFin()?->format('Y-m-d H:i'));
    }

    public function testDetecteLeChevauchementDeDeuxRendezVous(): void
    {
        $premier = $this->rendezVous('rdv-1', '2030-12-20 10:00:00');
        $second = $this->rendezVous('rdv-2', '2030-12-20 10:15:00');

        self::assertTrue($premier->chevauche($second));
        self::assertTrue($second->chevauche($premier));
    }

    public function testDeuxRendezVousContigusNeSeChevauchentPas(): void
    {
        $premier = $this->rendezVous('rdv-1', '2030-12-20 10:00:00');
        $second = $this->rendezVous('rdv-2', '2030-12-20 10:30:00');

        self::assertFalse($premier->chevauche($second));
    }

    public function testRefuseUnCreneauOccupe(): void
    {
        $praticien = $this->praticien();
        $praticien->chargerRendezVous([$this->rendezVous('rdv-1', '2030-12-20 10:00:00')]);

        $this->expectException(CreneauRendezVousIndisponibleException::class);
        RendezVous::creer(
            $praticien,
            new Patient('patient-1', 'Martin', 'Bob'),
            new DateTimeImmutable('2030-12-20 10:15:00'),
            'Consultation',
        );
    }

    public function testUnRendezVousAnnuleNeBloquePasLeCreneau(): void
    {
        $praticien = $this->praticien();
        $rendezVousAnnule = $this->rendezVous('rdv-1', '2030-12-20 10:00:00');
        $rendezVousAnnule->annuler(new DateTimeImmutable('2030-01-01'));
        $praticien->chargerRendezVous([$rendezVousAnnule]);

        $nouveau = RendezVous::creer(
            $praticien,
            new Patient('patient-1', 'Martin', 'Bob'),
            new DateTimeImmutable('2030-12-20 10:00:00'),
            'Consultation',
        );

        self::assertSame('2030-12-20 10:00', $nouveau->getDateHeureDebut()->format('Y-m-d H:i'));
    }
}
