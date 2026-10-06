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

    public function lister(): array
    {
        return array_map(PraticienResumeDTO::fromEntity(...), $this->repository->findAll());
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
