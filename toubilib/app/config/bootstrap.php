<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use toubilib\adapters\repositories\RendezVousRepository;
use toubilib\application\ports\api\ServiceRendezVousInterface;
use toubilib\application\ports\spi\RendezVousRepositoryInterface;
use toubilib\application\usecases\ServiceRendezVous;

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    PDO::class => static function (): PDO {
        $database = getenv('POSTGRES_DB');
        $username = getenv('POSTGRES_USER');
        $password = getenv('POSTGRES_PASSWORD');
        $host = getenv('POSTGRES_HOST') ?: 'toubilib.db';

        if ($database === false || $username === false || $password === false) {
            throw new RuntimeException('Configuration PostgreSQL manquante.');
        }

        return new PDO(
            "pgsql:host={$host};port=5432;dbname={$database}",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
    },
    RendezVousRepositoryInterface::class => DI\autowire(RendezVousRepository::class),
    ServiceRendezVousInterface::class => DI\autowire(ServiceRendezVous::class),
]);

$container = $containerBuilder->build();
AppFactory::setContainer($container);
$app = AppFactory::create();

require __DIR__ . '/routes.php';

$app->addRoutingMiddleware();
$app->addErrorMiddleware(false, true, true);

return $app;
