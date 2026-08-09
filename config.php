<?php

declare(strict_types=1);

// ── Chargement du .env (fallback hors Docker) ─────────────────────────────────
(static function (): void {
    $file = __DIR__ . '/.env';
    if (!is_file($file)) {
        return;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $k = trim(substr($line, 0, $pos));
        $v = trim(substr($line, $pos + 1));
        if (strlen($v) >= 2
            && (($v[0] === '"' && str_ends_with($v, '"'))
             || ($v[0] === "'" && str_ends_with($v, "'")))
        ) {
            $v = substr($v, 1, -1);
        }
        // Les variables injectées par Docker ont priorité
        if (getenv($k) === false) {
            putenv("{$k}={$v}");
        }
    }
})();

// ── Database ──────────────────────────────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: 'db');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
define('DB_NAME', getenv('DB_NAME') ?: 'planning');
define('DB_USER', getenv('DB_USER') ?: 'planning');
define('DB_PASS', getenv('DB_PASS') ?: '');

// ── JWT ───────────────────────────────────────────────────────────────────────
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'CHANGE_ME_planning_jwt_secret_32chars_min');
define('JWT_TTL',    (int)(getenv('JWT_TTL') ?: 86400));

// ── SMTP (password reset) ─────────────────────────────────────────────────────
define('SMTP_HOST',       getenv('SMTP_HOST')       ?: '');
define('SMTP_PORT',       (int)(getenv('SMTP_PORT') ?: 587));
define('SMTP_SECURE',     getenv('SMTP_SECURE')     ?: 'tls');
define('SMTP_USER',       getenv('SMTP_USER')       ?: '');
define('SMTP_PASS',       getenv('SMTP_PASS')       ?: '');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: '');
define('SMTP_FROM_NAME',  getenv('SMTP_FROM_NAME')  ?: 'Planning');
define('APP_URL',         getenv('APP_URL')         ?: 'http://localhost:8080');

// ── Cloudflare Turnstile (anti brute-force login) ─────────────────────────────
define('TURNSTILE_SITE_KEY',   getenv('TURNSTILE_SITE_KEY')   ?: '');
define('TURNSTILE_SECRET_KEY', getenv('TURNSTILE_SECRET_KEY') ?: '');

// ── Application ───────────────────────────────────────────────────────────────
define('APP_VERSION', '1.0.0');

// ── Timezone ──────────────────────────────────────────────────────────────────
date_default_timezone_set(getenv('TZ') ?: 'Europe/Paris');

// ── PDO factory ───────────────────────────────────────────────────────────────
function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
