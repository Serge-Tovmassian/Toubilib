<?php

declare(strict_types=1);

namespace toubilib\application\validators;

use toubilib\application\dto\CreateRdvDTO;
use toubilib\application\exceptions\PatientNotFoundException;
use toubilib\application\exceptions\PraticienNotFoundException;
use toubilib\application\ports\spi\PatientRepositoryInterface;
use toubilib\application\ports\spi\PraticienRepositoryInterface;
use toubilib\domain\entities\Patient;
use toubilib\domain\entities\RendezVous;

final readonly class CreateRendezVousValidator
{
    public function __construct(
        private PraticienRepositoryInterface $praticienRepository,
        private PatientRepositoryInterface $patientRepository,
    ) {
    }

    public function exigerPraticien(string $id): void
    {
        $this->praticienRepository->findById($id) ?? throw new PraticienNotFoundException($id);
    }

    public function exigerPatient(string $id): Patient
    {
        return $this->patientRepository->findById($id) ?? throw new PatientNotFoundException($id);
    }

    public function validerIndisponibilite(string $praticienId, \DateTimeImmutable $jour, string $periode): RendezVous
    {
        $praticien = $this->praticienRepository->findByIdWithRendezVousForDate($praticienId, $jour)
            ?? throw new PraticienNotFoundException($praticienId);

        return $praticien->creerIndisponibilite($jour, $periode);
    }

    public function validate(CreateRdvDTO $dto): RendezVous
    {
        $praticien = $this->praticienRepository->findByIdWithRendezVousForDate(
            $dto->praticienId,
            $dto->dateHeureDebut,
        );
        if ($praticien === null) {
            throw new PraticienNotFoundException($dto->praticienId);
        }

        $patient = $this->patientRepository->findById($dto->patientId);
        if ($patient === null) {
            throw new PatientNotFoundException($dto->patientId);
        }

        return RendezVous::creer(
            $praticien,
            $patient,
            $dto->dateHeureDebut,
            $dto->motifVisite,
        );
    }
}
