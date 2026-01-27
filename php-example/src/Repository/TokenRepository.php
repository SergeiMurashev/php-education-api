<?php

// src/Repository/Token.php
final class TokenRepository
{
    public function __construct(private PDO $pdo) {}

    public function store(string $userId, string $hash, int $ttl): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO refresh_tokens (user_id, token_hash, expires_at)
            VALUES (:uid, :hash, NOW() + INTERVAL '1 second' * :ttl)
        ");
        $stmt->execute([
            ':uid'  => $userId,
            ':hash' => $hash,
            ':ttl'  => $ttl,
        ]);
    }

    public function find(string $hash): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT rt.*, u.email, u.name, u.role
            FROM refresh_tokens rt
            JOIN users u ON u.id = rt.user_id
            WHERE rt.token_hash = :hash
            LIMIT 1
        ");
        $stmt->execute([':hash' => $hash]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function revoke(string $id): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE refresh_tokens SET revoked_at = NOW() WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
    }
}
