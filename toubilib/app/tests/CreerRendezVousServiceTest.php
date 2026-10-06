<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use toubilib\application\dto\CreateRdvDTO;
use toubilib\application\exceptions\PatientNotFoundException;
use toubilib\application\exceptions\PraticienNotFoundException;
use toubilib\application\ports\spi\PatientRepositoryInterface;
use toubilib\application\ports\spi\PraticienRepositoryInterface;
use toubilib\application\ports\spi\RendezVousRepositoryInterface;
use toubilib\application\usecases\ServiceRendezVous;
use toubilib\application\validators\CreateRendezVousValidator;
use toubilib\domain\entities\Patient;
use toubilib\domain\entities\Praticien;
use toubilib\domain\entities\RendezVous;
use toubilib\domain\entities\Specialite;

final class CreerRendezVousServiceTest extends TestCase
{
    private function praticien(): Praticien
    {
        return new Praticien(
            'praticien-1',
            'Dupont',
            'Alice',
            'Nancy',
            'alice@example.test',
            '0102030405',
            new Specialite(1, 'Médecine générale'),
        );
    }

    private function dto(string $praticienId = 'praticien-1', string $patientId = 'patient-1'): CreateRdvDTO
    {
        return new CreateRdvDTO(
            $praticienId,
            $patientId,
            new DateTimeImmutable('2030-12-20 10:00:00'),
            'Consultation',
        );
    }

    private function service(?Praticien $praticien, ?Patient $patient): ServiceRendezVous
    {
        $praticiens = new class($praticien) implements PraticienRepositoryInterface {
            public function __construct(private readonly ?Praticien $praticien) {}
            public function findAll(): array { return $this->praticien === null ? [] : [$this->praticien]; }
            public function findById(string $id): ?Praticien { return $this->praticien; }
        };
        $patients = new class($patient) implements PatientRepositoryInterface {
            public function __construct(private readonly ?Patient $patient) {}
            public function findById(string $id): ?Patient { return $this->patient; }
        };
        $rendezVous = new class implements RendezVousRepositoryInterface {
            public function findById(string $id): ?RendezVous { return null; }
            public function save(RendezVous $rendezVous): void {}
        };

        return new ServiceRendezVous(
            $rendezVous,
            new CreateRendezVousValidator($praticiens, $patients),
        );
    }

    public function testRetourneLeDtoLorsqueLePraticienEtLePatientExistent(): void
    {
        $dto = $this->dto();
        $service = $this->service($this->praticien(), new Patient('patient-1', 'Martin', 'Bob'));

        self::assertSame($dto, $service->creer($dto));
    }

    public function testRefuseUnPraticienInexistant(): void
    {
        $service = $this->service(null, new Patient('patient-1', 'Martin', 'Bob'));

        $this->expectException(PraticienNotFoundException::class);
        $service->creer($this->dto(praticienId: 'absent'));
    }

    public function testRefuseUnPatientInexistant(): void
    {
        $service = $this->service($this->praticien(), null);

        $this->expectException(PatientNotFoundException::class);
        $service->creer($this->dto(patientId: 'absent'));
    }
}
