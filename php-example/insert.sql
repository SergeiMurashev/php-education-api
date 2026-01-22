-- insert.sql
-- тестовые пользователи

INSERT INTO users (email, name)
VALUES
    ('sergey@royalty.dev', 'Сергей'),
    ('ivan@royalty.dev', 'Иван'),
    ('anna@royalty.dev', 'Анна'),
    ('petr@royalty.dev', 'Пётр'),
    ('kate@royalty.dev', 'Катя')
    ON CONFLICT (email) DO NOTHING;