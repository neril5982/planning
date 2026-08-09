<?php

declare(strict_types=1);

const USER_ROLES = ['admin', 'direction', 'user'];

// GET /admin/users — liste tous les utilisateurs
function handle_admin_users_list(string $method, array $params, ?array $user): void
{
    $rows = get_pdo()->query(
        'SELECT id, email, nom, prenom, role, last_login_at, created_at, updated_at FROM users ORDER BY id ASC'
    )->fetchAll();
    json_success($rows);
}

// POST /admin/users — crée un utilisateur (invitation par email d'activation)
function handle_admin_users_create(string $method, array $params, ?array $user): void
{
    $body   = request_body();
    $email  = trim(strtolower((string)($body['email']  ?? '')));
    $nom    = trim((string)($body['nom']    ?? ''));
    $prenom = trim((string)($body['prenom'] ?? ''));
    $role   = in_array($body['role'] ?? '', USER_ROLES, true) ? $body['role'] : 'user';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Données invalides', 400, ['email' => 'Email invalide']);
    }

    $pdo    = get_pdo();
    $exists = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
    $exists->execute([$email]);
    if ((int)$exists->fetchColumn() > 0) {
        json_error('Cet email est déjà utilisé', 409);
    }

    $unusable_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT, ['cost' => 10]);
    $token         = bin2hex(random_bytes(32));
    $expires       = date('Y-m-d H:i:s', strtotime('+7 days'));

    $pdo->prepare(
        'INSERT INTO users (email, password, nom, prenom, role, reset_token, reset_token_expires_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([$email, $unusable_hash, $nom ?: null, $prenom ?: null, $role, $token, $expires]);

    $id = (int)$pdo->lastInsertId();

    $init_url   = APP_URL . '/reset_password.php?token=' . $token;
    $prenom_esc = htmlspecialchars($prenom ?: 'utilisateur', ENT_QUOTES);
    $html = <<<HTML
    <div style="font-family:Arial,sans-serif;max-width:500px;margin:auto;padding:24px;">
      <h2 style="color:#1f2937;">Bienvenue sur le portail Gestion SARL Moncomble</h2>
      <p>Bonjour {$prenom_esc},</p>
      <p>Un compte a été créé pour vous. Cliquez sur le bouton ci-dessous pour choisir votre mot de passe et activer votre compte.</p>
      <p style="margin:24px 0;">
        <a href="{$init_url}"
           style="background:#2563eb;color:white;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;">
          Activer mon compte
        </a>
      </p>
      <p style="color:#6b7280;font-size:13px;">
        Ce lien expire dans 7 jours. Si vous n'attendiez pas cet email, vous pouvez l'ignorer.
      </p>
    </div>
    HTML;

    $sent = smtp_send($email, 'Activation de votre compte', $html);
    if (!$sent) {
        error_log("[admin_users_create] smtp_send failed for {$email}");
    }

    $stmt = $pdo->prepare('SELECT id, email, nom, prenom, role, created_at FROM users WHERE id = ?');
    $stmt->execute([$id]);
    json_success($stmt->fetch(), "Utilisateur créé, email d'activation envoyé", 201);
}

// PUT /admin/users/{id} — met à jour un utilisateur
function handle_admin_users_update(string $method, array $params, ?array $user): void
{
    $id = (int)($params['id'] ?? 0);
    if (!$id) {
        json_error('Identifiant invalide', 400);
    }

    $pdo  = get_pdo();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_error('Utilisateur non trouvé', 404);
    }

    $body   = request_body();
    $fields = [];
    $vals   = [];

    if (isset($body['nom']))    { $fields[] = 'nom = ?';    $vals[] = trim((string)$body['nom']) ?: null; }
    if (isset($body['prenom'])) { $fields[] = 'prenom = ?'; $vals[] = trim((string)$body['prenom']) ?: null; }
    if (isset($body['role']) && in_array($body['role'], USER_ROLES, true)) {
        $fields[] = 'role = ?';
        $vals[]   = $body['role'];
    }
    if (isset($body['email'])) {
        $email = strtolower(trim((string)$body['email']));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_error('Email invalide', 400, ['email' => 'Email invalide']);
        }
        $fields[] = 'email = ?';
        $vals[]   = $email;
    }
    if (!empty($body['password'])) {
        $policy_err = validate_password_policy((string)$body['password']);
        if ($policy_err !== null) {
            json_error($policy_err, 400, ['password' => $policy_err]);
        }
        $fields[] = 'password = ?';
        $vals[]   = password_hash((string)$body['password'], PASSWORD_BCRYPT, ['cost' => 10]);
    }

    if (!$fields) {
        json_error('Aucun champ à mettre à jour', 400);
    }

    $vals[] = $id;
    $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($vals);

    $out = $pdo->prepare('SELECT id, email, nom, prenom, role, updated_at FROM users WHERE id = ?');
    $out->execute([$id]);
    json_success($out->fetch(), 'Utilisateur mis à jour');
}

// DELETE /admin/users/{id} — supprime un utilisateur (jamais le dernier admin)
function handle_admin_users_delete(string $method, array $params, ?array $user): void
{
    $id = (int)($params['id'] ?? 0);
    if (!$id) {
        json_error('Identifiant invalide', 400);
    }

    $pdo  = get_pdo();
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        json_error('Utilisateur non trouvé', 404);
    }

    if ($row['role'] === 'admin') {
        $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        if ($adminCount <= 1) {
            json_error('Impossible de supprimer le dernier administrateur', 400);
        }
    }

    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    json_success(null, 'Utilisateur supprimé');
}
