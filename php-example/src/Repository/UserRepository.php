<?php

// src/Repository/User.php
final class UserRepository
{
    public function __construct(private PDO $pdo) {}

    public function create(string $email, string $name, string $hash): User
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (email, name, password_hash)
            VALUES (:email, :name, :hash)
            RETURNING id, email, name, role
        ");
        $stmt->execute([
            ':email' => $email,
            ':name'  => $name,
            ':hash'  => $hash,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new RuntimeException('user create failed');
        }

        return new User(
            $row['id'],
            $row['email'],
            $row['name'],
            $row['role'],
        );
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, email, name, role, password_hash
            FROM users
            WHERE email = :email AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}