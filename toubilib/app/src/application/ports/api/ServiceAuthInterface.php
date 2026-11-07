<?php

declare(strict_types=1);

namespace toubilib\application\ports\api;

interface ServiceAuthInterface
{
    /** Retourne un JWT signé. */
    public function connecter(string $email, string $motDePasse): string;
}
