<?php

declare(strict_types=1);

function handle_forgot_password(string $method, array $params, ?array $user): void
{
    $body  = request_body();
    $email = trim(strtolower((string)($body['email'] ?? '')));

    if ($email === '') {
        json_error('Email requis', 400, ['email' => 'Email requis']);
    }

    $pdo  = get_pdo();
    $stmt = $pdo->prepare("SELECT id, nom, prenom FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    // Always return success to prevent user enumeration
    if (!$row) {
        json_success(null, 'Si cet email existe, un lien de réinitialisation a été envoyé.');
    }

    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?")
        ->execute([$token, $expires, $row['id']]);

    $reset_url = APP_URL . '/reset_password.php?token=' . $token;
    $prenom    = htmlspecialchars($row['prenom'] ?? 'utilisateur', ENT_QUOTES);

    $html = <<<HTML
    <div style="font-family:Arial,sans-serif;max-width:500px;margin:auto;padding:24px;">
      <h2 style="color:#1f2937;">Réinitialisation de mot de passe</h2>
      <p>Bonjour {$prenom},</p>
      <p>Une demande de réinitialisation de mot de passe a été effectuée pour votre compte Planning.</p>
      <p style="margin:24px 0;">
        <a href="{$reset_url}"
           style="background:#2563eb;color:white;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;">
          Réinitialiser mon mot de passe
        </a>
      </p>
      <p style="color:#6b7280;font-size:13px;">
        Ce lien expire dans 1 heure. Si vous n'avez pas fait cette demande, ignorez cet email.
      </p>
    </div>
    HTML;

    $sent = smtp_send($email, 'Réinitialisation de mot de passe — Planning', $html);
    if (!$sent) {
        error_log("[forgot_password] smtp_send failed for {$email}");
    }

    json_success(null, 'Si cet email existe, un lien de réinitialisation a été envoyé.');
}
