<?php

declare(strict_types=1);

namespace toubilib\application\usecases;

use toubilib\application\dto\RendezVousDTO;
use toubilib\application\exceptions\RendezVousNotFoundException;
use toubilib\application\ports\api\ServiceRendezVousInterface;
use toubilib\application\ports\spi\RendezVousRepositoryInterface;

final class ServiceRendezVous implements ServiceRendezVousInterface
{
    public function __construct(
        private readonly RendezVousRepositoryInterface $repository,
    ) {
    }

    public function annuler(string $id): RendezVousDTO
    {
        $rendezVous = $this->repository->findById($id);
        if ($rendezVous === null) {
            throw new RendezVousNotFoundException($id);
        }
        $rendezVous->annuler();
        $this->repository->save($rendezVous);
        return RendezVousDTO::fromEntity($rendezVous);
    }
}
