<?php

declare(strict_types=1);

function handle_reset_password(string $method, array $params, ?array $user): void
{
    $body     = request_body();
    $token    = trim((string)($body['token']    ?? ''));
    $password = (string)($body['password'] ?? '');

    $errors = [];
    if ($token === '') $errors['token'] = 'Token requis';
    $policy_err = validate_password_policy($password);
    if ($policy_err !== null) $errors['password'] = $policy_err;
    if ($errors) json_error('Données invalides', 400, $errors);

    $pdo  = get_pdo();
    $stmt = $pdo->prepare(
        "SELECT id FROM users
         WHERE reset_token = ?
           AND reset_token_expires_at > NOW()
         LIMIT 1"
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if (!$row) {
        json_error('Token invalide ou expiré', 400, ['token' => 'Token invalide ou expiré']);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    $pdo->prepare(
        "UPDATE users
         SET password = ?, reset_token = NULL, reset_token_expires_at = NULL
         WHERE id = ?"
    )->execute([$hash, $row['id']]);

    json_success(null, 'Mot de passe mis à jour. Vous pouvez vous connecter.');
}
