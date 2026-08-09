<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/middleware/auth.php';

set_api_headers();

// OPTIONS preflight (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
// Strip /api prefix, normalise trailing slash
$path   = rtrim(preg_replace('#^/api#', '', $uri), '/') ?: '/';

// ── Route table ──────────────────────────────────────────────────────────────
// [ HTTP_METHOD, regex_pattern, include_file, handler_fn, require_auth, min_role ]
$routes = [
    // ── Auth (public) ────────────────────────────────────────────────────────
    ['POST', '#^/auth/login$#',           'auth/login.php',           'handle_login',           false, null],
    ['POST', '#^/auth/logout$#',          'auth/logout.php',          'handle_logout',          true,  null],
    ['POST', '#^/auth/forgot-password$#', 'auth/forgot_password.php', 'handle_forgot_password', false, null],
    ['POST', '#^/auth/reset-password$#',  'auth/reset_password.php',  'handle_reset_password',  false, null],

    // ── Session (sanity check) ──────────────────────────────────────────────
    ['GET',  '#^/me$#',                   'me.php',                   'handle_me',              true,  null],

    // ── Chantiers (planning) ─────────────────────────────────────────────────
    ['GET',    '#^/chantiers$#',              'chantiers.php', 'handle_chantiers_list',   true, null],
    ['POST',   '#^/chantiers$#',              'chantiers.php', 'handle_chantiers_create', true, null],
    ['PUT',    '#^/chantiers/(?P<id>\d+)$#',  'chantiers.php', 'handle_chantiers_update', true, null],
    ['DELETE', '#^/chantiers/(?P<id>\d+)$#',  'chantiers.php', 'handle_chantiers_delete', true, null],

    // ── Administration utilisateurs (admin uniquement) ───────────────────────
    ['GET',    '#^/admin/users$#',             'admin/users.php', 'handle_admin_users_list',   true, 'admin'],
    ['POST',   '#^/admin/users$#',             'admin/users.php', 'handle_admin_users_create', true, 'admin'],
    ['PUT',    '#^/admin/users/(?P<id>\d+)$#', 'admin/users.php', 'handle_admin_users_update', true, 'admin'],
    ['DELETE', '#^/admin/users/(?P<id>\d+)$#', 'admin/users.php', 'handle_admin_users_delete', true, 'admin'],
];

foreach ($routes as [$routeMethod, $pattern, $file, $handler, $needsAuth, $minRole]) {
    if ($routeMethod !== $method) {
        continue;
    }
    if (!preg_match($pattern, $path, $matches)) {
        continue;
    }

    $params = array_filter($matches, fn($k) => is_string($k), ARRAY_FILTER_USE_KEY);
    $user   = $needsAuth ? require_auth($minRole) : null;

    require_once __DIR__ . '/' . $file;
    $handler($method, $params, $user);
    exit;
}

json_error('Route introuvable', 404);
