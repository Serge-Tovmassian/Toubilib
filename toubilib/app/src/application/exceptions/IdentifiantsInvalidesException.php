<?php

declare(strict_types=1);

namespace toubilib\application\exceptions;

use RuntimeException;

final class IdentifiantsInvalidesException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Email ou mot de passe incorrect.');
    }
}
