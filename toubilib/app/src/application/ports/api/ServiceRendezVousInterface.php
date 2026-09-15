<?php

declare(strict_types=1);

namespace toubilib\application\ports\api;

use toubilib\application\dto\RendezVousDTO;

interface ServiceRendezVousInterface
{
    public function annuler(string $id): RendezVousDTO;
}
