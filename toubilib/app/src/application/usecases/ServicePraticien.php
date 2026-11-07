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
        // Filtre en mémoire : suffisant tant que la liste reste courte
        $egal = static fn (string $a, ?string $b): bool => $b === null || mb_strtolower($a) === mb_strtolower($b);
        $praticiens = array_filter(
            $this->repository->findAll(),
            fn ($p) => $egal($p->getSpecialite()->getLibelle(), $specialite) && $egal($p->getVille(), $ville),
        );

        return array_map(PraticienResumeDTO::fromEntity(...), array_values($praticiens));
    }

    public function disponibilites(string $id, \DateTimeImmutable $jour, string $motif): array
    {
        $praticien = $this->repository->findByIdWithRendezVousForDate($id, $jour);
        if ($praticien === null) {
            throw new PraticienNotFoundException($id);
        }

        return array_map(
            static fn (\DateTimeImmutable $d) => $d->format(DATE_ATOM),
            $praticien->creneauxDisponibles($jour, $motif),
        );
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
