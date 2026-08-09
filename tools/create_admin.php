<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function prompt(string $label): string
{
    echo $label;
    return trim((string)fgets(STDIN));
}

$email  = prompt('Email : ');
$nom    = prompt('Nom : ');
$prenom = prompt('Prénom : ');

if ($email === '') {
    fwrite(STDERR, "Email requis.\n");
    exit(1);
}

$pdo  = get_pdo();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    fwrite(STDERR, "Un utilisateur avec cet email existe déjà.\n");
    exit(1);
}

$tempPassword = bin2hex(random_bytes(9));
$hash          = password_hash($tempPassword, PASSWORD_BCRYPT, ['cost' => 10]);

$pdo->prepare(
    'INSERT INTO users (email, password, nom, prenom, role) VALUES (?, ?, ?, ?, ?)'
)->execute([$email, $hash, $nom ?: null, $prenom ?: null, 'admin']);

echo "Compte admin créé pour {$email}.\n";
echo "Mot de passe temporaire : {$tempPassword}\n";
echo "(à communiquer par un canal sécurisé — pensez à le faire changer à la première connexion)\n";
