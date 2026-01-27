<?php

use App\Repository\CommentRepository;
use App\Usecase\CommentUsecase;

$pdo = db();
$usecase = new CommentUsecase(
    new CommentRepository($pdo)
);

$userId = AuthMiddleware::userId(); // ← ты УЖЕ это вынес 👍

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

try {

    // GET /api/posts/{postId}/comments
    if (preg_match('#^/api/posts/([0-9a-f-]{36})/comments$#', $path, $m)
        && $method === 'GET'
    ) {
        Response::ok(
            $usecase->list($m[1])
        );
    }

    // POST /api/posts/{postId}/comments
    if (preg_match('#^/api/posts/([0-9a-f-]{36})/comments$#', $path, $m)
        && $method === 'POST'
    ) {
        $data = Request::json();
        Response::created(
            $usecase->create(
                $m[1],
                $userId,
                $data['content'] ?? ''
            )
        );
    }

    // PUT /api/comments/{id}
    if (preg_match('#^/api/comments/([0-9a-f-]{36})$#', $path, $m)
        && $method === 'PUT'
    ) {
        $data = Request::json();
        Response::ok(
            $usecase->update(
                $m[1],
                $userId,
                $data['content'] ?? ''
            )
        );
    }

    // DELETE /api/comments/{id}
    if (preg_match('#^/api/comments/([0-9a-f-]{36})$#', $path, $m)
        && $method === 'DELETE'
    ) {
        $usecase->delete($m[1], $userId);
        Response::ok(['ok' => true]);
    }

    Response::notFound();

} catch (DomainException $e) {
    Response::error($e->getMessage(), 422);
}