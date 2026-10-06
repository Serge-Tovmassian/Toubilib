<?php

declare(strict_types=1);

namespace toubilib\application\ports\spi;

use DateTimeImmutable;
use toubilib\domain\entities\Praticien;

interface PraticienRepositoryInterface
{
    /** @return list<Praticien> */
    public function findAll(): array;

    public function findById(string $id): ?Praticien;

    public function findByIdWithRendezVousForDate(string $id, DateTimeImmutable $date): ?Praticien;
}
