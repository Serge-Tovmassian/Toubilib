<?php

declare(strict_types=1);

namespace toubilib\application\ports\api;

use toubilib\application\dto\PraticienDTO;

interface ServicePraticienInterface
{
    /** @return list<\toubilib\application\dto\PraticienResumeDTO> */
    public function lister(?string $specialite = null, ?string $ville = null): array;

    /** @return list<string> débuts de créneaux libres (ISO 8601) */
    public function disponibilites(string $id, \DateTimeImmutable $jour, string $motif): array;

    public function obtenirParId(string $id): PraticienDTO;
}
