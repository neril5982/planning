<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../api/helpers.php';

$_token       = $_COOKIE['plan_token'] ?? '';
$current_user = $_token ? jwt_decode($_token) : null;

if (!$current_user) {
    $next = $_SERVER['REQUEST_URI'] ?? '/index.php';
    header('Location: /login.php?next=' . urlencode($next));
    exit;
}
