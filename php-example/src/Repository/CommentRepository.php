<?php

// src/Repository/Comment.php
final class CommentRepository
{
    public function __construct(private PDO $pdo) {}

    public function listByPost(string $postId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.id, c.content, c.created_at, c.updated_at,
                   u.id AS user_id, u.name
            FROM comments c
            JOIN users u ON u.id = c.user_id
            WHERE c.post_id = :post_id AND c.deleted_at IS NULL
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([':post_id' => $postId]);

        return $stmt->fetchAll();
    }

    public function create(string $postId, string $userId, string $content): array
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO comments (post_id, user_id, content)
            VALUES (:post_id, :user_id, :content)
            RETURNING id, content, created_at
        ");
        $stmt->execute([
            ':post_id' => $postId,
            ':user_id' => $userId,
            ':content' => $content,
        ]);

        return $stmt->fetch();
    }

    public function update(string $id, string $userId, string $content): ?array
    {
        $stmt = $this->pdo->prepare("
            UPDATE comments
            SET content = :content, updated_at = NOW()
            WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL
            RETURNING id, content, updated_at
        ");
        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
            ':content' => $content,
        ]);

        return $stmt->fetch() ?: null;
    }

    public function delete(string $id, string $userId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE comments
            SET deleted_at = NOW()
            WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL
        ");
        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }
}