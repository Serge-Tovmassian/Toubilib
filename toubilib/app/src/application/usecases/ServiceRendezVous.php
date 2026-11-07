<?php

declare(strict_types=1);

namespace toubilib\application\usecases;

use DateTimeImmutable;
use toubilib\application\dto\CreateRdvDTO;
use toubilib\application\dto\RendezVousDTO;
use toubilib\application\exceptions\RendezVousNotFoundException;
use toubilib\application\ports\api\ServiceRendezVousInterface;
use toubilib\application\ports\spi\RendezVousRepositoryInterface;
use toubilib\application\validators\CreateRendezVousValidator;

final class ServiceRendezVous implements ServiceRendezVousInterface
{
    public function __construct(
        private readonly RendezVousRepositoryInterface $repository,
        private readonly CreateRendezVousValidator $createValidator,
    ) {
    }

    public function creer(CreateRdvDTO $dto): CreateRdvDTO
    {
        $this->createValidator->validate($dto);

        return $dto;
    }

    public function annuler(string $id): RendezVousDTO
    {
        $rendezVous = $this->trouver($id);
        $rendezVous->annuler();
        $this->repository->save($rendezVous);
        return RendezVousDTO::fromEntity($rendezVous);
    }

    public function obtenirParId(string $id): RendezVousDTO
    {
        return RendezVousDTO::fromEntity($this->trouver($id));
    }

    public function agenda(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array
    {
        return array_map(
            RendezVousDTO::fromEntity(...),
            $this->repository->findByPraticienEtPeriode($praticienId, $debut, $fin),
        );
    }

    private function trouver(string $id): \toubilib\domain\entities\RendezVous
    {
        $rendezVous = $this->repository->findById($id);
        if ($rendezVous === null) {
            throw new RendezVousNotFoundException($id);
        }

        return $rendezVous;
    }
}
