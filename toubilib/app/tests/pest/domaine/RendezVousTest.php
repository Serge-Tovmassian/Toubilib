<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use toubilib\domain\entities\EtatRendezVous;
use toubilib\domain\entities\RendezVous;
use toubilib\domain\exceptions\TransitionEtatRendezVousException;

final class RendezVousTest extends TestCase
{
    private function rendezVous(DateTimeImmutable $debut, EtatRendezVous $etat = EtatRendezVous::PLANIFIE): RendezVous
    {
        return new RendezVous(
            id: 'rdv-1',
            praticienId: 'praticien-1',
            patientId: 'patient-1',
            dateHeureDebut: $debut,
            duree: 30,
            etat: $etat,
        );
    }

    public function testUnRendezVousFuturPlanifiePeutEtreAnnule(): void
    {
        $maintenant = new DateTimeImmutable('2026-10-01 10:00:00');
        $rendezVous = $this->rendezVous(new DateTimeImmutable('2026-10-02 10:00:00'));

        $rendezVous->annuler($maintenant);

        self::assertSame(EtatRendezVous::ANNULE, $rendezVous->getEtat());
    }

    public function testUnRendezVousPasseNePeutPasEtreAnnule(): void
    {
        $maintenant = new DateTimeImmutable('2026-10-02 10:00:00');
        $rendezVous = $this->rendezVous(new DateTimeImmutable('2026-10-01 10:00:00'));

        try {
            $rendezVous->annuler($maintenant);
            self::fail('Une exception métier était attendue.');
        } catch (TransitionEtatRendezVousException) {
            self::assertSame(EtatRendezVous::PLANIFIE, $rendezVous->getEtat());
        }
    }

    public function testUnRendezVousQuiCommenceMaintenantNePeutPasEtreAnnule(): void
    {
        $maintenant = new DateTimeImmutable('2026-10-01 10:00:00');
        $rendezVous = $this->rendezVous($maintenant);

        $this->expectException(TransitionEtatRendezVousException::class);
        $rendezVous->annuler($maintenant);
    }

    public function testUnRendezVousDejaAnnuleNePeutPasEtreAnnuleUneDeuxiemeFois(): void
    {
        $maintenant = new DateTimeImmutable('2026-10-01 10:00:00');
        $rendezVous = $this->rendezVous(new DateTimeImmutable('2026-10-02 10:00:00'));
        $rendezVous->annuler($maintenant);

        $this->expectException(TransitionEtatRendezVousException::class);
        $rendezVous->annuler($maintenant);
    }

    public function testUnRendezVousHonoreNePeutPasEtreAnnule(): void
    {
        $maintenant = new DateTimeImmutable('2026-10-01 10:00:00');
        $rendezVous = $this->rendezVous(
            new DateTimeImmutable('2026-10-02 10:00:00'),
            EtatRendezVous::HONORE,
        );

        $this->expectException(TransitionEtatRendezVousException::class);
        $rendezVous->annuler($maintenant);
    }
}
