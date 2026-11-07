<?php

declare(strict_types=1);

// Crée un compte par patient et par praticien (mot de passe commun, démo uniquement).
// Usage : php bin/seed-users.php [mot_de_passe]   (variables POSTGRES_* requises)

$motDePasse = $argv[1] ?? 'toubilib';
$pdo = new PDO(
    sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        getenv('POSTGRES_HOST') ?: 'toubilib.db',
        getenv('POSTGRES_PORT') ?: '5432',
        getenv('POSTGRES_DB'),
    ),
    getenv('POSTGRES_USER'),
    getenv('POSTGRES_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);

$hash = password_hash($motDePasse, PASSWORD_BCRYPT);
$insert = $pdo->prepare(
    'INSERT INTO users (id, email, password_hash, role, profil_id) VALUES (:id, :email, :hash, :role, :profil) '
    . 'ON CONFLICT (email) DO NOTHING',
);

foreach (['patient', 'praticien'] as $table) {
    $profils = $pdo->query("SELECT id, email FROM {$table} WHERE email IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($profils as $profil) {
        $insert->execute([
            'id' => bin2hex(random_bytes(16)),
            'email' => $profil['email'],
            'hash' => $hash,
            'role' => $table,
            'profil' => $profil['id'],
        ]);
    }
}

echo "Comptes créés (mot de passe : {$motDePasse}).\n";
