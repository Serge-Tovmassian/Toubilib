<?php

declare(strict_types=1);

namespace toubilib\application\ports\spi;

use toubilib\domain\entities\Utilisateur;

interface UtilisateurRepositoryInterface
{
    public function findByEmail(string $email): ?Utilisateur;
}
