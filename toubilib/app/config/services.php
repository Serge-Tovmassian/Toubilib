<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use toubilib\adapters\repositories\PatientRepository;
use toubilib\adapters\repositories\PraticienRepository;
use toubilib\adapters\repositories\RendezVousRepository;
use toubilib\adapters\repositories\UtilisateurRepository;
use toubilib\application\ports\api\ServiceAuthInterface;
use toubilib\application\ports\spi\UtilisateurRepositoryInterface;
use toubilib\application\usecases\ServiceAuth;
use toubilib\application\ports\api\ServicePraticienInterface;
use toubilib\application\ports\api\ServiceRendezVousInterface;
use toubilib\application\ports\spi\PraticienRepositoryInterface;
use toubilib\application\ports\spi\PatientRepositoryInterface;
use toubilib\application\ports\spi\RendezVousRepositoryInterface;
use toubilib\application\usecases\ServicePraticien;
use toubilib\application\usecases\ServiceRendezVous;
use toubilib\application\validators\CreateRendezVousValidator;

use function DI\create;
use function DI\get;

return [
    PDO::class => static function (ContainerInterface $container): PDO {
        $database = $container->get('settings')['database'];
        if ($database['name'] === null || $database['username'] === null || $database['password'] === null) {
            throw new RuntimeException('Configuration PostgreSQL manquante.');
        }

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s',
            $database['driver'],
            $database['host'],
            $database['port'],
            $database['name'],
        );

        return new PDO(
            $dsn,
            $database['username'],
            $database['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
    },
    PraticienRepositoryInterface::class => create(PraticienRepository::class)
        ->constructor(get(PDO::class)),
    PatientRepositoryInterface::class => create(PatientRepository::class)
        ->constructor(get(PDO::class)),
    RendezVousRepositoryInterface::class => create(RendezVousRepository::class)
        ->constructor(get(PDO::class)),
    UtilisateurRepositoryInterface::class => create(UtilisateurRepository::class)
        ->constructor(get(PDO::class)),
    'jwt.secret' => static function (ContainerInterface $container): string {
        $secret = $container->get('settings')['jwt_secret'];
        if ($secret === null || strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET manquant ou trop court (32 caractères minimum).');
        }

        return $secret;
    },
    ServiceAuthInterface::class => create(ServiceAuth::class)
        ->constructor(get(UtilisateurRepositoryInterface::class), get('jwt.secret')),
    CreateRendezVousValidator::class => create(CreateRendezVousValidator::class)
        ->constructor(
            get(PraticienRepositoryInterface::class),
            get(PatientRepositoryInterface::class),
        ),
    ServicePraticienInterface::class => create(ServicePraticien::class)
        ->constructor(get(PraticienRepositoryInterface::class)),
    ServiceRendezVousInterface::class => create(ServiceRendezVous::class)
        ->constructor(
            get(RendezVousRepositoryInterface::class),
            get(CreateRendezVousValidator::class),
        ),
];
