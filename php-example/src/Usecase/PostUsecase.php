<?php

// src/Usecase/Post.php
final class PostUsecase
{
    public function __construct(
        private PostRepository $repo
    ) {}

    public function list(): array
    {
        return $this->repo->list();
    }

    public function get(string $id): array
    {
        $post = $this->repo->get($id);
        if (!$post) {
            throw new DomainException('post not found');
        }
        return $post;
    }

    public function create(string $userId, string $title, string $body): array
    {
        if ($title === '' || $body === '') {
            throw new DomainException('title and body are required');
        }

        return $this->repo->create($userId, $title, $body);
    }

    public function update(
        string $postId,
        string $userId,
        ?string $title,
        ?string $body
    ): array {
        if ($title === null && $body === null) {
            throw new DomainException('nothing to update');
        }

        $post = $this->repo->update($postId, $userId, $title, $body);
        if (!$post) {
            throw new DomainException('post not found or forbidden');
        }

        return $post;
    }

    public function delete(string $postId, string $userId): void
    {
        if (!$this->repo->delete($postId, $userId)) {
            throw new DomainException('post not found or forbidden');
        }
    }
}
