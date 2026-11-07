<?php

declare(strict_types=1);

namespace toubilib\application\usecases;

use Firebase\JWT\JWT;
use toubilib\application\exceptions\IdentifiantsInvalidesException;
use toubilib\application\ports\api\ServiceAuthInterface;
use toubilib\application\ports\spi\UtilisateurRepositoryInterface;

final class ServiceAuth implements ServiceAuthInterface
{
    public const DUREE_JETON = 3600;

    public function __construct(
        private readonly UtilisateurRepositoryInterface $repository,
        private readonly string $secret,
    ) {
    }

    public function connecter(string $email, string $motDePasse): string
    {
        $utilisateur = $this->repository->findByEmail($email);
        if ($utilisateur === null || !$utilisateur->verifierMotDePasse($motDePasse)) {
            throw new IdentifiantsInvalidesException();
        }

        $maintenant = time();

        return JWT::encode([
            'sub' => $utilisateur->id,
            'role' => $utilisateur->role,
            'profil_id' => $utilisateur->profilId,
            'iat' => $maintenant,
            'exp' => $maintenant + self::DUREE_JETON,
        ], $this->secret, 'HS256');
    }
}
