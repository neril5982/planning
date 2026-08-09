<?php

declare(strict_types=1);

// ── JSON response helpers ─────────────────────────────────────────────────────

function json_success(mixed $data = null, string $message = '', int $status = 200): never
{
    http_response_code($status);
    echo json_encode([
        'success' => true,
        'data'    => $data,
        'message' => $message,
        'errors'  => [],
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function json_error(string $message, int $status = 400, array $errors = []): never
{
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'data'    => null,
        'message' => $message,
        'errors'  => $errors,
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

// ── Cloudflare Turnstile ──────────────────────────────────────────────────────

function verify_turnstile(string $token, string $remoteIp): bool
{
    if (TURNSTILE_SECRET_KEY === '') {
        return true;
    }
    if ($token === '') {
        return false;
    }

    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'secret'   => TURNSTILE_SECRET_KEY,
            'response' => $token,
            'remoteip' => $remoteIp,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);

    if ($raw === false) {
        return false;
    }
    $result = json_decode($raw, true);
    return is_array($result) && ($result['success'] ?? false) === true;
}

// ── CORS + Content-Type ───────────────────────────────────────────────────────

function set_api_headers(): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

// ── Request body ──────────────────────────────────────────────────────────────

function request_body(): array
{
    $raw = file_get_contents('php://input');
    return (array)(json_decode($raw ?: '{}', true) ?? []);
}

// ── JWT (HS256, no external deps) ─────────────────────────────────────────────

function jwt_b64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function jwt_b64url_decode(string $data): string
{
    $pad = strlen($data) % 4;
    return base64_decode(strtr($data, '-_', '+/') . ($pad ? str_repeat('=', 4 - $pad) : ''));
}

function jwt_encode(array $payload): string
{
    $header = jwt_b64url_encode((string)json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $body   = jwt_b64url_encode((string)json_encode($payload));
    $sig    = jwt_b64url_encode(hash_hmac('sha256', "{$header}.{$body}", JWT_SECRET, true));
    return "{$header}.{$body}.{$sig}";
}

function jwt_decode(string $token): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    [$header, $body, $sig] = $parts;
    $expected = jwt_b64url_encode(hash_hmac('sha256', "{$header}.{$body}", JWT_SECRET, true));
    if (!hash_equals($expected, $sig)) {
        return null;
    }
    $data = json_decode(jwt_b64url_decode($body), true);
    if (!is_array($data)) {
        return null;
    }
    if (isset($data['exp']) && $data['exp'] < time()) {
        return null;
    }
    return $data;
}

function jwt_for_user(array $user): string
{
    return jwt_encode([
        'sub'    => $user['id'],
        'email'  => $user['email'],
        'nom'    => $user['nom'],
        'prenom' => $user['prenom'],
        'role'   => $user['role'],
        'iat'    => time(),
        'exp'    => time() + JWT_TTL,
    ]);
}

// ── Bearer token extraction ───────────────────────────────────────────────────

function bearer_token(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION']
           ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
           ?? getallheaders()['Authorization']
           ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        return $m[1];
    }
    // Fallback : cookie plan_token (quand le reverse proxy strip le header Authorization)
    return $_COOKIE['plan_token'] ?? null;
}

// ── SMTP mailer ───────────────────────────────────────────────────────────────

/**
 * Sends a transactional HTML email via SMTP.
 * Falls back to PHP mail() when SMTP_HOST is empty.
 */
function smtp_send(string $to, string $subject, string $html): bool
{
    if (SMTP_HOST === '') {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
        return mail($to, $subject, $html, $headers);
    }

    try {
        $prefix = SMTP_SECURE === 'ssl' ? 'ssl://' : '';
        $sock   = @fsockopen($prefix . SMTP_HOST, SMTP_PORT, $errno, $errstr, 15);
        if (!$sock) {
            throw new RuntimeException("SMTP connect failed: {$errstr} ({$errno})");
        }
        stream_set_timeout($sock, 15);

        // Lit une réponse SMTP (potentiellement multi-lignes : "250-..." puis "250 ...")
        // et lève une exception si le code n'est pas 2xx/3xx — sans ça, un rejet du
        // serveur (RCPT TO refusé, quota dépassé, relay denied...) passait inaperçu et
        // smtp_send() retournait true alors que rien n'avait été envoyé.
        $recv = function (string $step) use ($sock) {
            $last = '';
            do {
                $line = fgets($sock, 512);
                if ($line === false) {
                    throw new RuntimeException("SMTP: pas de réponse du serveur après {$step}");
                }
                $last = $line;
            } while (isset($line[3]) && $line[3] === '-');
            $code = (int)substr($last, 0, 3);
            if ($code >= 400) {
                throw new RuntimeException("SMTP {$step} rejeté : " . trim($last));
            }
            return $last;
        };
        $send = fn(string $cmd) => fwrite($sock, $cmd . "\r\n");

        $recv('connexion'); // 220 greeting

        $send("EHLO localhost");
        $recv('EHLO');

        if (SMTP_SECURE === 'tls') {
            $send("STARTTLS");
            $recv('STARTTLS');
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $send("EHLO localhost");
            $recv('EHLO (post-TLS)');
        }

        if (SMTP_USER !== '') {
            $send("AUTH LOGIN");
            $recv('AUTH LOGIN');
            $send(base64_encode(SMTP_USER));
            $recv('AUTH (login)');
            $send(base64_encode(SMTP_PASS));
            $recv('AUTH (password)');
        }

        $send("MAIL FROM:<" . SMTP_FROM_EMAIL . ">");
        $recv('MAIL FROM');
        $send("RCPT TO:<{$to}>");
        $recv('RCPT TO');
        $send("DATA");
        $recv('DATA');

        $enc_subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $message  = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
        $message .= "To: {$to}\r\n";
        $message .= "Subject: {$enc_subject}\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "\r\n" . $html . "\r\n.\r\n";

        $send($message);
        $recv('envoi du message');
        $send("QUIT");
        fclose($sock);
        return true;

    } catch (Throwable $e) {
        error_log("smtp_send error (to={$to}): " . $e->getMessage());
        return false;
    }
}

// ── Password policy ───────────────────────────────────────────────────────────

function validate_password_policy(string $pwd): ?string
{
    if (strlen($pwd) < 12)                   return 'Le mot de passe doit faire au moins 12 caractères';
    if (!preg_match('/[A-Z]/', $pwd))        return 'Le mot de passe doit contenir au moins une majuscule';
    if (!preg_match('/[^a-zA-Z0-9]/', $pwd)) return 'Le mot de passe doit contenir au moins un caractère spécial';
    return null;
}
