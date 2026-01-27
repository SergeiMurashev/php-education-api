<?php

// src/Domain/Comment.php
final class Comment
{
    public function __construct(
        public string $id,
        public string $postId,
        public string $userId,
        public string $content,
        public string $createdAt,
        public ?string $updatedAt = null,
        public ?string $deletedAt = null,
    ) {}
}