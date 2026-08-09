<?php

declare(strict_types=1);

/**
 * Verifies the Bearer JWT and returns the decoded user payload.
 * Sends 401 if the token is absent/invalid, 403 if the role is insufficient.
 *
 * @param  string|null $min_role  'admin' → admins only ; 'direction' → admin+direction ; null → any logged-in user.
 * @return array  JWT payload (sub, email, nom, prenom, role, iat, exp)
 */

const ROLE_RANK = ['admin' => 3, 'direction' => 2, 'user' => 1];

function require_auth(?string $min_role = null): array
{
    $token = bearer_token();

    if ($token === null) {
        json_error('Authentification requise', 401);
    }

    $payload = jwt_decode($token);

    if ($payload === null) {
        json_error('Token invalide ou expiré', 401);
    }

    if ($min_role !== null) {
        $userRank = ROLE_RANK[$payload['role'] ?? ''] ?? 0;
        $minRank  = ROLE_RANK[$min_role]              ?? 0;
        if ($userRank < $minRank) {
            json_error('Accès non autorisé', 403);
        }
    }

    return $payload;
}
