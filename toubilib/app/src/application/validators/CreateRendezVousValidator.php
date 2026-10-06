<?php

declare(strict_types=1);

namespace toubilib\application\validators;

use toubilib\application\dto\CreateRdvDTO;
use toubilib\application\exceptions\PatientNotFoundException;
use toubilib\application\exceptions\PraticienNotFoundException;
use toubilib\application\ports\spi\PatientRepositoryInterface;
use toubilib\application\ports\spi\PraticienRepositoryInterface;

final readonly class CreateRendezVousValidator
{
    public function __construct(
        private PraticienRepositoryInterface $praticienRepository,
        private PatientRepositoryInterface $patientRepository,
    ) {
    }

    public function validate(CreateRdvDTO $dto): void
    {
        if ($this->praticienRepository->findById($dto->praticienId) === null) {
            throw new PraticienNotFoundException($dto->praticienId);
        }

        if ($this->patientRepository->findById($dto->patientId) === null) {
            throw new PatientNotFoundException($dto->patientId);
        }
    }
}
