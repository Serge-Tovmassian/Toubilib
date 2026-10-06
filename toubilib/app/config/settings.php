<?php

declare(strict_types=1);

return [
    'settings' => [
        'database' => [
            'driver' => getenv('POSTGRES_DRIVER') ?: 'pgsql',
            'host' => getenv('POSTGRES_HOST') ?: 'toubilib.db',
            'port' => getenv('POSTGRES_PORT') ?: '5432',
            'name' => getenv('POSTGRES_DB') ?: null,
            'username' => getenv('POSTGRES_USER') ?: null,
            'password' => getenv('POSTGRES_PASSWORD') ?: null,
        ],
    ],
];
