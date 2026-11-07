<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAutowiring(false);
$containerBuilder->addDefinitions(__DIR__ . '/settings.php');
$containerBuilder->addDefinitions(__DIR__ . '/services.php');
$containerBuilder->addDefinitions(__DIR__ . '/api.php');

$container = $containerBuilder->build();
$app = AppFactory::createFromContainer($container);

require __DIR__ . '/routes.php';

$app->addRoutingMiddleware();
$app->addErrorMiddleware(false, true, true);

// CORS : ajouté en dernier = exécuté en premier, donc les erreurs portent aussi les en-têtes.
$app->add(function ($request, $handler) {
    $response = $request->getMethod() === 'OPTIONS'
        ? new \Slim\Psr7\Response(204)
        : $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', $request->getHeaderLine('Origin') ?: '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Vary', 'Origin');
});

return $app;
