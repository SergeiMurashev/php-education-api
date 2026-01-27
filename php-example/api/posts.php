<?php

use App\Http\Middleware\AuthMiddleware;
use App\Repository\PostRepository;
use App\Usecase\PostUsecase;

$pdo = db();
$userId = AuthMiddleware::requireUserId();

$usecase = new PostUsecase(
    new PostRepository($pdo)
);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

try {
    // GET /api/posts
    if ($path === '/api/posts' && $method === 'GET') {
        Response::ok($usecase->list());
    }

    // POST /api/posts
    if ($path === '/api/posts' && $method === 'POST') {
        $data = Request::json();
        Response::created(
            $usecase->create(
                $userId,
                $data['title'] ?? '',
                $data['body'] ?? ''
            )
        );
    }

    // /api/posts/{id}
    if (preg_match('#^/api/posts/([0-9a-f-]{36})$#', $path, $m)) {
        $id = $m[1];

        if ($method === 'GET') {
            Response::ok($usecase->get($id));
        }

        if ($method === 'PUT') {
            $data = Request::json();
            Response::ok(
                $usecase->update(
                    $id,
                    $userId,
                    $data['title'] ?? null,
                    $data['body'] ?? null
                )
            );
        }

        if ($method === 'DELETE') {
            $usecase->delete($id, $userId);
            Response::ok(['ok' => true]);
        }
    }

    Response::notFound();

} catch (DomainException $e) {
    Response::error($e->getMessage(), 422);
}