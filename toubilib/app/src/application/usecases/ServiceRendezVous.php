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

    public function creer(CreateRdvDTO $dto): RendezVousDTO
    {
        $rendezVous = $this->createValidator->validate($dto);
        $this->repository->insert($rendezVous);

        return RendezVousDTO::fromEntity($rendezVous);
    }

    public function annuler(string $id): RendezVousDTO
    {
        $rendezVous = $this->trouver($id);
        $rendezVous->annuler();
        $this->repository->save($rendezVous);
        return RendezVousDTO::fromEntity($rendezVous);
    }

    public function honorer(string $id): RendezVousDTO
    {
        $rendezVous = $this->trouver($id);
        $rendezVous->honorer();
        $this->repository->save($rendezVous);
        return RendezVousDTO::fromEntity($rendezVous);
    }

    public function ignorer(string $id): RendezVousDTO
    {
        $rendezVous = $this->trouver($id);
        $rendezVous->marquerNonHonore();
        $this->repository->save($rendezVous);
        return RendezVousDTO::fromEntity($rendezVous);
    }

    public function declarerIndisponibilite(string $praticienId, DateTimeImmutable $jour, string $periode): RendezVousDTO
    {
        $indisponibilite = $this->createValidator->validerIndisponibilite($praticienId, $jour, $periode);
        $this->repository->insert($indisponibilite);

        return RendezVousDTO::fromEntity($indisponibilite);
    }

    public function obtenirParId(string $id): RendezVousDTO
    {
        return RendezVousDTO::fromEntity($this->trouver($id));
    }

    public function historique(string $patientId): array
    {
        return array_map(RendezVousDTO::fromEntity(...), $this->repository->findByPatient($patientId));
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
