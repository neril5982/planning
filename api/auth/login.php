<?php

declare(strict_types=1);

function handle_login(string $method, array $params, ?array $user): void
{
    $body           = request_body();
    $email          = trim((string)($body['email']    ?? ''));
    $password       = (string)($body['password'] ?? '');
    $turnstileToken = (string)($body['turnstile_token'] ?? '');

    $errors = [];
    if ($email === '')    $errors['email']    = 'Email requis';
    if ($password === '') $errors['password'] = 'Mot de passe requis';
    if ($errors)          json_error('Champs manquants', 400, $errors);

    if (!verify_turnstile($turnstileToken, $_SERVER['REMOTE_ADDR'] ?? '')) {
        json_error('Vérification anti-robot échouée, veuillez réessayer', 400);
    }

    $pdo  = get_pdo();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    // Constant-time comparison to prevent user enumeration timing attacks
    if (!$row || !password_verify($password, (string)$row['password'])) {
        json_error('Email ou mot de passe incorrect', 401);
    }

    // Rehash if needed (e.g. cost upgrade)
    if (password_needs_rehash((string)$row['password'], PASSWORD_BCRYPT, ['cost' => 10])) {
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
            ->execute([password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]), $row['id']]);
    }

    $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$row['id']]);

    $token = jwt_for_user($row);

    json_success([
        'token' => $token,
        'user'  => [
            'id'     => $row['id'],
            'email'  => $row['email'],
            'nom'    => $row['nom'],
            'prenom' => $row['prenom'],
            'role'   => $row['role'],
        ],
    ], 'Connexion réussie');
}
