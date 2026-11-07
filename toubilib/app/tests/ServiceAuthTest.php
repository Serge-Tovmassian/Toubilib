<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPUnit\Framework\TestCase;
use toubilib\application\exceptions\IdentifiantsInvalidesException;
use toubilib\application\ports\spi\UtilisateurRepositoryInterface;
use toubilib\application\usecases\ServiceAuth;
use toubilib\domain\entities\Utilisateur;

final class ServiceAuthTest extends TestCase
{
    private const SECRET = 'secret-de-test-assez-long-pour-hs256!';

    private function service(): ServiceAuth
    {
        $repository = new class implements UtilisateurRepositoryInterface {
            public function findByEmail(string $email): ?Utilisateur
            {
                return $email === 'bob@x.test'
                    ? new Utilisateur('u1', $email, password_hash('secret', PASSWORD_BCRYPT), 'patient', 'pat-1')
                    : null;
            }
        };

        return new ServiceAuth($repository, self::SECRET);
    }

    public function testEmetUnJetonAvecRoleEtProfil(): void
    {
        $claims = JWT::decode($this->service()->connecter('bob@x.test', 'secret'), new Key(self::SECRET, 'HS256'));

        self::assertSame('patient', $claims->role);
        self::assertSame('pat-1', $claims->profil_id);
    }

    public function testRefuseUnMauvaisMotDePasse(): void
    {
        $this->expectException(IdentifiantsInvalidesException::class);
        $this->service()->connecter('bob@x.test', 'faux');
    }

    public function testRefuseUnEmailInconnu(): void
    {
        $this->expectException(IdentifiantsInvalidesException::class);
        $this->service()->connecter('inconnu@x.test', 'secret');
    }
}
