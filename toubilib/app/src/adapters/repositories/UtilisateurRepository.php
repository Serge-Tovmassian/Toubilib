<?php

declare(strict_types=1);

namespace toubilib\adapters\repositories;

use PDO;
use toubilib\application\ports\spi\UtilisateurRepositoryInterface;
use toubilib\domain\entities\Utilisateur;

final class UtilisateurRepository implements UtilisateurRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?Utilisateur
    {
        $statement = $this->pdo->prepare(
            'SELECT id, email, password_hash, role, profil_id FROM users WHERE email = :email',
        );
        $statement->execute(['email' => $email]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false
            ? null
            : new Utilisateur($row['id'], $row['email'], $row['password_hash'], $row['role'], $row['profil_id']);
    }
}
