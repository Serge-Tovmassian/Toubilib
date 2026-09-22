<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use toubilib\adapters\actions\AnnulerRendezVousAction;
use toubilib\application\dto\RendezVousDTO;
use toubilib\application\exceptions\RendezVousNotFoundException;
use toubilib\application\ports\api\ServiceRendezVousInterface;
use toubilib\domain\exceptions\TransitionEtatRendezVousException;

final class AnnulerRendezVousActionTest extends TestCase
{
    private function appeler(Closure $annuler): Response
    {
        $service = new class($annuler) implements ServiceRendezVousInterface {
            public function __construct(private readonly Closure $annuler)
            {
            }

            public function annuler(string $id): RendezVousDTO
            {
                return ($this->annuler)($id);
            }
        };

        $action = new AnnulerRendezVousAction($service);
        $request = (new ServerRequestFactory())->createServerRequest('PATCH', '/rendez-vous/rdv-1/annulation');

        return $action($request, new Response(), ['id' => 'rdv-1']);
    }

    public function testRetourneLeRendezVousAnnuleEnJson(): void
    {
        $response = $this->appeler(static function (string $id): RendezVousDTO {
            self::assertSame('rdv-1', $id);
            return new RendezVousDTO($id, 'praticien-1', 'patient-1', '2026-10-02T10:00:00+02:00', null, 30, 'ANNULE', null);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertStringStartsWith('application/json', $response->getHeaderLine('Content-Type'));
        self::assertSame('ANNULE', json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR)['etat']);
    }

    public function testRetourne404SiLeRendezVousEstAbsent(): void
    {
        $response = $this->appeler(static function (string $id): never {
            throw new RendezVousNotFoundException($id);
        });

        self::assertSame(404, $response->getStatusCode());
    }

    public function testRetourne409SiLAnnulationEstInterdite(): void
    {
        $response = $this->appeler(static function (): never {
            throw new TransitionEtatRendezVousException('Annulation interdite.');
        });

        self::assertSame(409, $response->getStatusCode());
    }
}
