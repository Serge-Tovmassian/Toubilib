<?php

declare(strict_types=1);

namespace toubilib\application\dto;

use JsonSerializable;
use toubilib\domain\entities\Praticien;

final readonly class PraticienDTO implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $nom,
        public string $prenom,
        public string $titre,
        public string $ville,
        public string $email,
        public string $telephone,
        public int $specialiteId,
        public string $specialite,
        public ?string $descriptionSpecialite,
        public ?string $structureId,
        public ?string $rppsId,
        public bool $organisation,
        public bool $accepteNouveauPatient,
    ) {
    }

    public static function fromEntity(Praticien $praticien): self
    {
        $specialite = $praticien->getSpecialite();

        return new self(
            $praticien->getId(),
            $praticien->getNom(),
            $praticien->getPrenom(),
            $praticien->getTitre(),
            $praticien->getVille(),
            $praticien->getEmail(),
            $praticien->getTelephone(),
            $specialite->getId(),
            $specialite->getLibelle(),
            $specialite->getDescription(),
            $praticien->getStructureId(),
            $praticien->getRppsId(),
            $praticien->estUneOrganisation(),
            $praticien->accepteNouveauPatient(),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'titre' => $this->titre,
            'ville' => $this->ville,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'specialite' => [
                'id' => $this->specialiteId,
                'libelle' => $this->specialite,
                'description' => $this->descriptionSpecialite,
            ],
            'structure_id' => $this->structureId,
            'rpps_id' => $this->rppsId,
            'organisation' => $this->organisation,
            'nouveau_patient' => $this->accepteNouveauPatient,
        ];
    }
}
