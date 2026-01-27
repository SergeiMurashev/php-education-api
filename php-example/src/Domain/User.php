<?php

// src/Domain/User.php
final class User
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
        public string $role,
        public ?string $deletedAt = null,
    ) {}
}
