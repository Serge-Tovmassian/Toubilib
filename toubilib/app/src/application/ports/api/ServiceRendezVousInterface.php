<?php

declare(strict_types=1);

namespace toubilib\application\ports\api;

use DateTimeImmutable;
use toubilib\application\dto\CreateRdvDTO;
use toubilib\application\dto\RendezVousDTO;

interface ServiceRendezVousInterface
{
    public function creer(CreateRdvDTO $dto): RendezVousDTO;

    public function obtenirParId(string $id): RendezVousDTO;

    public function annuler(string $id): RendezVousDTO;

    public function honorer(string $id): RendezVousDTO;

    public function ignorer(string $id): RendezVousDTO;

    public function declarerIndisponibilite(string $praticienId, DateTimeImmutable $jour, string $periode): RendezVousDTO;

    /** @return list<RendezVousDTO> */
    public function historique(string $patientId): array;

    /** @return list<RendezVousDTO> */
    public function agenda(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array;
}
