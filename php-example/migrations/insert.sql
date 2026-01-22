
-- 1) users
INSERT INTO users (email, name, password_hash)
VALUES
    ('sergey@royalty.dev', 'Сергей', crypt('123456', gen_salt('bf'))),
    ('ivan@royalty.dev',   'Иван',   crypt('123456', gen_salt('bf'))),
    ('anna@royalty.dev',   'Анна',   crypt('123456', gen_salt('bf'))),
    ('petr@royalty.dev',   'Пётр',   crypt('123456', gen_salt('bf'))),
    ('kate@royalty.dev',   'Катя',   crypt('123456', gen_salt('bf')))
ON CONFLICT (email) DO NOTHING;

-- 2) posts (привязываем по email через SELECT id)
INSERT INTO posts (user_id, title, body)
VALUES
    ((SELECT id FROM users WHERE email='sergey@royalty.dev'), 'Первый пост Сергея', 'Это тело первого поста Сергея.'),
    ((SELECT id FROM users WHERE email='sergey@royalty.dev'), 'Второй пост Сергея', 'Это тело второго поста Сергея.'),
    ((SELECT id FROM users WHERE email='ivan@royalty.dev'),   'Первый пост Ивана',  'Это тело первого поста Ивана.'),
    ((SELECT id FROM users WHERE email='anna@royalty.dev'),   'Первый пост Анны',   'Это тело первого поста Анны.'),
    ((SELECT id FROM users WHERE email='petr@royalty.dev'),   'Первый пост Петра',  'Это тело первого поста Петра.'),
    ((SELECT id FROM users WHERE email='kate@royalty.dev'),   'Первый пост Кати',   'Это тело первого поста Кати.');