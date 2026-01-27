<?php

// src/Service/Token.php
final class TokenService
{
    public function __construct(
        private TokenRepository $repo,
        private array $config,
    ) {}

    public function issue(User $user): array
    {
        $now = time();

        $accessTtl  = $this->config['access_ttl_seconds'];
        $refreshTtl = $this->config['refresh_ttl_seconds'];

        $access = jwt_encode([
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'iat' => $now,
            'exp' => $now + $accessTtl,
        ], $this->config['jwt_secret']);

        $refresh = bin2hex(random_bytes(32));
        $this->repo->store(
            $user->id,
            hash('sha256', $refresh),
            $refreshTtl
        );

        return [
            'access_token' => $access,
            'refresh_token' => $refresh,
        ];
    }
}