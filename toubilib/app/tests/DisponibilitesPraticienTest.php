<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use toubilib\domain\entities\Praticien;
use toubilib\domain\entities\RendezVous;
use toubilib\domain\entities\Specialite;

final class DisponibilitesPraticienTest extends TestCase
{
    private function praticien(): Praticien
    {
        return new Praticien('p1', 'Dupont', 'Alice', 'Nancy', 'a@x.test', '0102030405', new Specialite(1, 'Généraliste'));
    }

    public function testCreneauxLibresDuJour(): void
    {
        $jour = new DateTimeImmutable('2030-12-20'); // vendredi
        $praticien = $this->praticien();
        $praticien->chargerRendezVous([
            new RendezVous('r1', 'p1', 'pat', new DateTimeImmutable('2030-12-20 10:00'), 30),
        ]);

        $libres = array_map(
            static fn (DateTimeImmutable $d) => $d->format('H:i'),
            $praticien->creneauxDisponibles($jour, 'CI', new DateTimeImmutable('2030-01-01')),
        );

        self::assertSame('08:00', $libres[0]);
        self::assertNotContains('10:00', $libres);
        self::assertContains('10:30', $libres);
        self::assertSame('17:30', end($libres)); // 17:30 + 30 min = 18:00 (fermeture)
    }

    public function testAucunCreneauUnJourFerme(): void
    {
        $samedi = new DateTimeImmutable('2030-12-21');

        self::assertSame([], $this->praticien()->creneauxDisponibles($samedi, 'C0', new DateTimeImmutable('2030-01-01')));
    }
}
