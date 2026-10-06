<?php

declare(strict_types=1);

namespace toubilib\application\dto;

use JsonSerializable;
use toubilib\domain\entities\Praticien;

final readonly class PraticienResumeDTO implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $nom,
        public string $prenom,
        public string $titre,
        public string $ville,
        public string $specialite,
    ) {
    }

    public static function fromEntity(Praticien $praticien): self
    {
        return new self(
            $praticien->getId(),
            $praticien->getNom(),
            $praticien->getPrenom(),
            $praticien->getTitre(),
            $praticien->getVille(),
            $praticien->getSpecialite()->getLibelle(),
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
