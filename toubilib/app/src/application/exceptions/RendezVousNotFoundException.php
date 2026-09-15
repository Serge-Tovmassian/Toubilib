<?php

declare(strict_types=1);

namespace toubilib\application\exceptions;

use RuntimeException;

final class RendezVousNotFoundException extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Le rendez-vous {$id} n'existe pas.");
    }
}
