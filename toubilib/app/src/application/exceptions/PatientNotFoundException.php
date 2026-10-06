<?php

declare(strict_types=1);

namespace toubilib\application\exceptions;

use RuntimeException;

final class PatientNotFoundException extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Patient introuvable : {$id}");
    }
}
