<?php

declare(strict_types=1);

namespace toubilib\domain\entities;

final readonly class Utilisateur
{
    public function __construct(
        public string $id,
        public string $email,
        public string $passwordHash,
        public string $role,
        public string $profilId,
    ) {
    }

    public function verifierMotDePasse(string $motDePasse): bool
    {
        return password_verify($motDePasse, $this->passwordHash);
    }
}
