<?php

declare(strict_types=1);

namespace toubilib\application\ports\api;

use toubilib\application\dto\PraticienDTO;

interface ServicePraticienInterface
{
    /** @return list<\toubilib\application\dto\PraticienResumeDTO> */
    public function lister(?string $specialite = null, ?string $ville = null): array;

    public function obtenirParId(string $id): PraticienDTO;
}
