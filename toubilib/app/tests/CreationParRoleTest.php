<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use toubilib\adapters\actions\CreerRendezVousAction;
use toubilib\application\dto\CreateRdvDTO;
use toubilib\application\dto\RendezVousDTO;
use toubilib\application\ports\api\ServiceRendezVousInterface;
use toubilib\application\ports\spi\PatientRepositoryInterface;
use toubilib\application\ports\spi\PraticienRepositoryInterface;
use toubilib\application\ports\spi\RendezVousRepositoryInterface;
use toubilib\application\usecases\ServiceRendezVous;
use toubilib\application\validators\CreateRendezVousValidator;
use toubilib\domain\entities\Patient;
use toubilib\domain\entities\Praticien;
use toubilib\domain\entities\RendezVous;

final class CreationParRoleTest extends TestCase
{
    private function creer(?string $role, ?string $profil): int
    {
        $service = new class implements ServiceRendezVousInterface {
            public function creer(CreateRdvDTO $dto): RendezVousDTO
            {
                return new RendezVousDTO('rdv-1', $dto->praticienId, $dto->patientId, '2030-01-07T10:00:00+00:00', null, 30, 'PLANIFIE', 'CI');
            }
            public function obtenirParId(string $id): RendezVousDTO { throw new LogicException(); }
            public function annuler(string $id): RendezVousDTO { throw new LogicException(); }
            public function honorer(string $id): RendezVousDTO { throw new LogicException(); }
            public function ignorer(string $id): RendezVousDTO { throw new LogicException(); }
            public function declarerIndisponibilite(string $praticienId, DateTimeImmutable $jour, string $periode): RendezVousDTO { throw new LogicException(); }
            public function historique(string $patientId): array { return []; }
            public function agenda(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array { return []; }
        };
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/rdvs');
        $request->getBody()->write(json_encode([
            'praticien_id' => 'prat-1',
            'patient_id' => 'pat-1',
            'date_heure_debut' => '2030-01-07T10:00:00+00:00',
            'motif_visite' => 'CI',
        ]));
        if ($role !== null) {
            $request = $request->withAttribute('auth', ['role' => $role, 'profil_id' => $profil]);
        }

        return (new CreerRendezVousAction($service))($request, new Response())->getStatusCode();
    }

    public function testPatientEtPraticienCreentPourEuxSeulement(): void
    {
        self::assertSame(201, $this->creer('patient', 'pat-1'));
        self::assertSame(403, $this->creer('patient', 'pat-2'));
        self::assertSame(201, $this->creer('praticien', 'prat-1'));
        self::assertSame(403, $this->creer('praticien', 'prat-2'));
        self::assertSame(403, $this->creer(null, null));
    }

    public function testAgendaIndiqueLePatientConcerne(): void
    {
        $debut = new DateTimeImmutable('2030-01-07 10:00');
        $rdvs = [
            new RendezVous('r1', 'prat-1', 'pat-1', $debut, 30, null, null, 'CI'),
            RendezVous::indisponibilite('prat-1', $debut->modify('+1 hour'), 60),
        ];
        $repository = new class($rdvs) implements RendezVousRepositoryInterface {
            public function __construct(private readonly array $rdvs) {}
            public function findById(string $id): ?RendezVous { return null; }
            public function findByPraticienEtPeriode(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array { return $this->rdvs; }
            public function findByPatient(string $patientId): array { return []; }
            public function insert(RendezVous $rendezVous): void {}
            public function save(RendezVous $rendezVous): void {}
        };
        $praticiens = new class implements PraticienRepositoryInterface {
            public function findAll(): array { return []; }
            public function findById(string $id): ?Praticien { return new Praticien($id, 'Dupont', 'Alice', 'Nancy', 'a@b.c', '0102030405', new toubilib\domain\entities\Specialite(1, 'MG')); }
            public function findByIdWithRendezVousForDate(string $id, DateTimeImmutable $date): ?Praticien { return $this->findById($id); }
        };
        $patients = new class implements PatientRepositoryInterface {
            public function findById(string $id): ?Patient { return new Patient($id, 'Martin', 'Bob'); }
        };

        $agenda = (new ServiceRendezVous($repository, new CreateRendezVousValidator($praticiens, $patients)))
            ->agenda('prat-1', $debut, $debut->modify('+1 day'));

        self::assertSame('Bob Martin', $agenda[0]->patientNom);
        self::assertSame('Bob Martin', $agenda[0]->jsonSerialize()['patient_nom']);
        self::assertArrayNotHasKey('patient_nom', $agenda[1]->jsonSerialize());
    }
}
