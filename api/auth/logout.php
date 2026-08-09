<?php

declare(strict_types=1);

// JWT is stateless — logout is handled client-side by discarding the token.
// This endpoint exists so the frontend can call it cleanly.
function handle_logout(string $method, array $params, ?array $user): void
{
    json_success(null, 'Déconnexion réussie');
}
