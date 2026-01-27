<?php

// src/Domain/Post.php
final class Post
{
    public function __construct(
        public string $id,
        public string $userId,
        public string $title,
        public string $body,
        public string $createdAt,
        public ?string $updatedAt = null,
    ) {}
}