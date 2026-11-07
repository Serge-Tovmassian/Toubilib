<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use toubilib\adapters\actions\ListerPraticiensAction;
use toubilib\adapters\actions\ObtenirPraticienAction;
use toubilib\adapters\actions\ObtenirRendezVousAction;
use toubilib\application\dto\PraticienDTO;
use toubilib\application\dto\PraticienResumeDTO;
use toubilib\application\dto\CreateRdvDTO;
use toubilib\application\dto\RendezVousDTO;
use toubilib\application\exceptions\PraticienNotFoundException;
use toubilib\application\exceptions\RendezVousNotFoundException;
use toubilib\application\ports\api\ServicePraticienInterface;
use toubilib\application\ports\api\ServiceRendezVousInterface;

final class ActionsConsultationTest extends TestCase
{
    public function testListeLesPraticiensEnJson(): void
    {
        $service = new class implements ServicePraticienInterface {
            public function lister(): array
            {
                return [new PraticienResumeDTO('p1', 'Dupont', 'Alice', 'Dr.', 'Nancy', 'Cardiologie')];
            }
            public function obtenirParId(string $id): PraticienDTO { throw new LogicException(); }
        };
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/praticiens');

        $response = (new ListerPraticiensAction($service))($request, new Response());

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('p1', json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR)[0]['id']);
    }

    public function testRetourne404PourUnPraticienAbsent(): void
    {
        $service = new class implements ServicePraticienInterface {
            public function lister(): array { return []; }
            public function obtenirParId(string $id): PraticienDTO { throw new PraticienNotFoundException($id); }
        };
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/praticiens/absent');

        $response = (new ObtenirPraticienAction($service))($request, new Response(), ['id' => 'absent']);

        self::assertSame(404, $response->getStatusCode());
    }

    public function testRetourneLeRendezVousEnJson(): void
    {
        $service = new class implements ServiceRendezVousInterface {
            public function creer(CreateRdvDTO $dto): CreateRdvDTO { return $dto; }
            public function obtenirParId(string $id): RendezVousDTO
            {
                return new RendezVousDTO($id, 'p1', 'patient-1', '2030-01-01T10:00:00+01:00', null, 30, 'PLANIFIE', null);
            }
            public function annuler(string $id): RendezVousDTO { throw new LogicException(); }
        };
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/rendez-vous/rdv-1');

        $response = (new ObtenirRendezVousAction($service))($request, new Response(), ['id' => 'rdv-1']);

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('rdv-1', $body['rendez_vous']['id']);
        self::assertSame('/rdvs/rdv-1', $body['links']['self']['href']);
    }

    public function testRetourne404PourUnRendezVousAbsent(): void
    {
        $service = new class implements ServiceRendezVousInterface {
            public function creer(CreateRdvDTO $dto): CreateRdvDTO { return $dto; }
            public function obtenirParId(string $id): RendezVousDTO { throw new RendezVousNotFoundException($id); }
            public function annuler(string $id): RendezVousDTO { throw new LogicException(); }
        };
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/rendez-vous/absent');

        $response = (new ObtenirRendezVousAction($service))($request, new Response(), ['id' => 'absent']);

        self::assertSame(404, $response->getStatusCode());
    }
}
