<?php

declare(strict_types=1);

namespace toubilib\domain\entities;

final readonly class Specialite
{
    public function __construct(
        private int $id,
        private string $libelle,
        private ?string $description = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
