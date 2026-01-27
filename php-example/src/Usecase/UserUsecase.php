<?php

// src/Usecase/User.php
final class UserUsecase
{
    public function __construct(
        private UserRepository $repo
    ) {}

    public function list(bool $all, bool $deleted): array
    {
        if ($all) return $this->repo->list('all');
        if ($deleted) return $this->repo->list('deleted');
        return $this->repo->list();
    }

    public function get(string $id): array
    {
        $user = $this->repo->get($id);
        if (!$user) {
            throw new DomainException('user not found');
        }
        return $user;
    }

    public function create(string $email, string $name): array
    {
        if ($email === '' || $name === '') {
            throw new DomainException('email and name are required');
        }

        $password = bin2hex(random_bytes(6));

        $user = $this->repo->create(
            strtolower($email),
            $name,
            password_hash($password, PASSWORD_BCRYPT)
        );

        return [
            'user' => $user,
            'temporary_password' => $password,
        ];
    }

    public function update(string $id, ?string $email, ?string $name): array
    {
        if ($email === null && $name === null) {
            throw new DomainException('nothing to update');
        }

        $user = $this->repo->update($id, $email, $name);
        if (!$user) {
            throw new DomainException('user not found');
        }

        return $user;
    }

    public function delete(string $id): array
    {
        $user = $this->repo->softDelete($id);
        if (!$user) {
            throw new DomainException('user not found or already deleted');
        }

        return $user;
    }

    public function restore(string $id): array
    {
        $user = $this->repo->restore($id);
        if (!$user) {
            throw new DomainException('user not found or not deleted');
        }

        return $user;
    }
}
