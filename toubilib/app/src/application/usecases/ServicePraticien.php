<?php

declare(strict_types=1);

namespace toubilib\application\usecases;

use toubilib\application\dto\PraticienDTO;
use toubilib\application\dto\PraticienResumeDTO;
use toubilib\application\exceptions\PraticienNotFoundException;
use toubilib\application\ports\api\ServicePraticienInterface;
use toubilib\application\ports\spi\PraticienRepositoryInterface;

final class ServicePraticien implements ServicePraticienInterface
{
    public function __construct(private readonly PraticienRepositoryInterface $repository)
    {
    }

    public function lister(?string $specialite = null, ?string $ville = null): array
    {
        // ponytail: filtre en mémoire (liste courte) ; passer en SQL si la table grossit
        $egal = static fn (string $a, ?string $b): bool => $b === null || mb_strtolower($a) === mb_strtolower($b);
        $praticiens = array_filter(
            $this->repository->findAll(),
            fn ($p) => $egal($p->getSpecialite()->getLibelle(), $specialite) && $egal($p->getVille(), $ville),
        );

        return array_map(PraticienResumeDTO::fromEntity(...), array_values($praticiens));
    }

    public function obtenirParId(string $id): PraticienDTO
    {
        $praticien = $this->repository->findById($id);
        if ($praticien === null) {
            throw new PraticienNotFoundException($id);
        }

        return PraticienDTO::fromEntity($praticien);
    }
}
