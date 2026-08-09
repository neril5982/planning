<?php

declare(strict_types=1);

function handle_me(string $method, array $params, ?array $user): void
{
    json_success(['user' => $user]);
}
