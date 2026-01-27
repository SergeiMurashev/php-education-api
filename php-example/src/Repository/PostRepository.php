<?php

// src/Repository/Post.php
final class PostRepository
{
    public function __construct(private PDO $pdo) {}

    public function list(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, user_id, title, body, created_at, updated_at
            FROM posts
            WHERE deleted_at IS NULL
            ORDER BY created_at DESC
        ");

        return $stmt->fetchAll();
    }

    public function get(string $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, user_id, title, body, created_at, updated_at
            FROM posts
            WHERE id = :id AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function create(string $userId, string $title, string $body): array
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO posts (user_id, title, body)
            VALUES (:uid, :title, :body)
            RETURNING id, user_id, title, body, created_at, updated_at
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':title' => $title,
            ':body' => $body,
        ]);

        return $stmt->fetch();
    }

    public function update(
        string $postId,
        string $userId,
        ?string $title,
        ?string $body
    ): ?array {
        $stmt = $this->pdo->prepare("
            UPDATE posts
            SET title = COALESCE(:title, title),
                body  = COALESCE(:body, body),
                updated_at = NOW()
            WHERE id = :id
              AND user_id = :user_id
              AND deleted_at IS NULL
            RETURNING id, user_id, title, body, created_at, updated_at
        ");

        $stmt->execute([
            ':id' => $postId,
            ':user_id' => $userId,
            ':title' => $title,
            ':body' => $body,
        ]);

        return $stmt->fetch() ?: null;
    }

    public function delete(string $postId, string $userId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE posts
            SET deleted_at = NOW(), updated_at = NOW()
            WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL
            RETURNING id
        ");
        $stmt->execute([
            ':id' => $postId,
            ':user_id' => $userId,
        ]);

        return (bool)$stmt->fetch();
    }
}
