<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use toubilib\application\exceptions\PraticienNotFoundException;
use toubilib\application\exceptions\RendezVousNotFoundException;
use toubilib\application\ports\spi\PraticienRepositoryInterface;
use toubilib\application\ports\spi\PatientRepositoryInterface;
use toubilib\application\ports\spi\RendezVousRepositoryInterface;
use toubilib\application\usecases\ServicePraticien;
use toubilib\application\usecases\ServiceRendezVous;
use toubilib\application\validators\CreateRendezVousValidator;
use toubilib\domain\entities\Patient;
use toubilib\domain\entities\Praticien;
use toubilib\domain\entities\RendezVous;
use toubilib\domain\entities\Specialite;

final class ServicesConsultationTest extends TestCase
{
    private function validateurCreation(): CreateRendezVousValidator
    {
        $praticien = $this->praticien();
        $praticiens = new class($praticien) implements PraticienRepositoryInterface {
            public function __construct(private readonly Praticien $praticien) {}
            public function findAll(): array { return [$this->praticien]; }
            public function findById(string $id): ?Praticien { return $this->praticien; }
            public function findByIdWithRendezVousForDate(string $id, DateTimeImmutable $date): ?Praticien
            {
                return $this->praticien;
            }
        };
        $patients = new class implements PatientRepositoryInterface {
            public function findById(string $id): ?Patient { return new Patient($id, 'Martin', 'Bob'); }
        };

        return new CreateRendezVousValidator($praticiens, $patients);
    }

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
        );
    }

    public function testListeLesPraticiensSousFormeDeDtos(): void
    {
        $praticien = $this->praticien();
        $repository = new class($praticien) implements PraticienRepositoryInterface {
            public function __construct(private readonly Praticien $praticien) {}
            public function findAll(): array { return [$this->praticien]; }
            public function findById(string $id): ?Praticien { return $this->praticien; }
            public function findByIdWithRendezVousForDate(string $id, DateTimeImmutable $date): ?Praticien
            {
                return $this->praticien;
            }
        };

        $resultat = (new ServicePraticien($repository))->lister();

        self::assertCount(1, $resultat);
        self::assertSame('praticien-1', $resultat[0]->id);
        self::assertSame('Médecine générale', $resultat[0]->specialite);

        $service = new ServicePraticien($repository);
        self::assertCount(1, $service->lister('médecine GÉNÉRALE', $praticien->getVille()));
        self::assertCount(0, $service->lister('Dentiste'));
        self::assertCount(0, $service->lister(null, 'Paris-introuvable'));
    }

    public function testRetourneLeDetailDuPraticien(): void
    {
        $praticien = $this->praticien();
        $repository = new class($praticien) implements PraticienRepositoryInterface {
            public function __construct(private readonly Praticien $praticien) {}
            public function findAll(): array { return []; }
            public function findById(string $id): ?Praticien { return $this->praticien; }
            public function findByIdWithRendezVousForDate(string $id, DateTimeImmutable $date): ?Praticien
            {
                return $this->praticien;
            }
        };

        $resultat = (new ServicePraticien($repository))->obtenirParId('praticien-1');

        self::assertSame('Alice', $resultat->prenom);
    }

    public function testSignaleUnPraticienAbsent(): void
    {
        $repository = new class implements PraticienRepositoryInterface {
            public function findAll(): array { return []; }
            public function findById(string $id): ?Praticien { return null; }
            public function findByIdWithRendezVousForDate(string $id, DateTimeImmutable $date): ?Praticien
            {
                return null;
            }
        };

        $this->expectException(PraticienNotFoundException::class);
        (new ServicePraticien($repository))->obtenirParId('absent');
    }

    public function testRetourneLeDetailDuRendezVous(): void
    {
        $rendezVous = new RendezVous(
            'rdv-1',
            'praticien-1',
            'patient-1',
            new DateTimeImmutable('+1 day'),
            30,
        );
        $repository = new class($rendezVous) implements RendezVousRepositoryInterface {
            public function __construct(private readonly RendezVous $rendezVous) {}
            public function findById(string $id): ?RendezVous { return $this->rendezVous; }
            public function findByPraticienEtPeriode(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array { return []; }
            public function insert(RendezVous $rendezVous): void {}
            public function save(RendezVous $rendezVous): void {}
        };

        $resultat = (new ServiceRendezVous($repository, $this->validateurCreation()))->obtenirParId('rdv-1');

        self::assertSame('rdv-1', $resultat->id);
    }

    public function testSignaleUnRendezVousAbsent(): void
    {
        $repository = new class implements RendezVousRepositoryInterface {
            public function findById(string $id): ?RendezVous { return null; }
            public function findByPraticienEtPeriode(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array { return []; }
            public function insert(RendezVous $rendezVous): void {}
            public function save(RendezVous $rendezVous): void {}
        };

        $this->expectException(RendezVousNotFoundException::class);
        (new ServiceRendezVous($repository, $this->validateurCreation()))->obtenirParId('absent');
    }
}
