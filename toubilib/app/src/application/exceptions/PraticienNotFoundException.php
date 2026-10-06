<?php

declare(strict_types=1);

namespace toubilib\application\exceptions;

use RuntimeException;

final class PraticienNotFoundException extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Praticien introuvable : {$id}");
    }
}
