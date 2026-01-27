<?php

// src/Usecase/Comment.php
final class CommentUsecase
{
    public function __construct(
        private CommentRepository $repo
    ) {}

    public function list(string $postId): array
    {
        return $this->repo->listByPost($postId);
    }

    public function create(string $postId, string $userId, string $content): array
    {
        if ($content === '') {
            throw new DomainException('content is required');
        }

        return $this->repo->create($postId, $userId, $content);
    }

    public function update(string $commentId, string $userId, string $content): array
    {
        if ($content === '') {
            throw new DomainException('content is required');
        }

        $comment = $this->repo->update($commentId, $userId, $content);
        if (!$comment) {
            throw new DomainException('comment not found or forbidden');
        }

        return $comment;
    }

    public function delete(string $commentId, string $userId): void
    {
        if (!$this->repo->delete($commentId, $userId)) {
            throw new DomainException('comment not found or forbidden');
        }
    }
}