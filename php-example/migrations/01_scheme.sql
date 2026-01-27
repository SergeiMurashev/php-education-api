CREATE EXTENSION IF NOT EXISTS "pgcrypto";

CREATE TABLE IF NOT EXISTS users (
                                     id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                                     email TEXT NOT NULL UNIQUE,
                                     name TEXT NOT NULL,
                                     password_hash TEXT NOT NULL,
                                     created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                                     updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                                     deleted_at TIMESTAMPTZ NULL,
                                     role TEXT NOT NULL DEFAULT 'user'
);

CREATE INDEX IF NOT EXISTS idx_users_deleted_at ON users(deleted_at);

CREATE TABLE IF NOT EXISTS posts (
                                     id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                                     user_id UUID NOT NULL REFERENCES users(id),
                                     title TEXT NOT NULL,
                                     body TEXT NOT NULL,
                                     created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                                     updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                                     deleted_at TIMESTAMPTZ NULL
);

CREATE INDEX IF NOT EXISTS idx_posts_user_id ON posts(user_id);
CREATE INDEX IF NOT EXISTS idx_posts_deleted_at ON posts(deleted_at);

ALTER TABLE users
    ADD CONSTRAINT users_role_check
        CHECK (role IN ('user', 'admin'));

CREATE TABLE IF NOT EXISTS refresh_tokens (
                                              id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                                              user_id UUID NOT NULL REFERENCES users(id),
                                              token_hash TEXT NOT NULL UNIQUE,
                                              expires_at TIMESTAMPTZ NOT NULL,
                                              created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                                              revoked_at TIMESTAMPTZ NULL
);

CREATE INDEX IF NOT EXISTS idx_refresh_tokens_user_id ON refresh_tokens(user_id);
CREATE INDEX IF NOT EXISTS idx_refresh_tokens_expires_at ON refresh_tokens(expires_at);

CREATE TABLE IF NOT EXISTS comments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    post_id UUID NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id),
    content TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMPTZ NULL
    );

CREATE INDEX IF NOT EXISTS idx_comments_post_id ON comments(post_id);
CREATE INDEX IF NOT EXISTS idx_comments_user_id ON comments(user_id);
CREATE INDEX IF NOT EXISTS idx_comments_deleted_at ON comments(deleted_at);