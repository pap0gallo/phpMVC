DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS users;

CREATE TABLE IF NOT EXISTS posts (
                                     id INTEGER PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
                                     title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    author VARCHAR(255) NOT NULL
    );

CREATE TABLE IF NOT EXISTS users (
                                     id INTEGER PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
                                     nickname VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL
    );

INSERT INTO posts (title, body, author) VALUES
                                            ('Первый пост', 'Это содержимое первого поста.', 'tirion'),
                                            ('Второй пост', 'Это содержимое второго поста.', 'tirion'),
                                            ('Третий пост', 'Это содержимое третьего поста.', 'tirion'),
                                            ('Четвертый пост', 'Это содержимое четвертого поста.', 'tirion'),
                                            ('Пятый пост', 'Это содержимое пятого поста.', 'tirion');

INSERT INTO users (nickname, password_hash) VALUES
                                                ('tirion', '$2y$10$pi1ydkQ89W0f34boN5ZCJuXbjgogem0uqqGs4e9o.GOMsN2wZxHDC'),
                                                ('jon', '$2y$10$pi1ydkQ89W0f34boN5ZCJuXbjgogem0uqqGs4e9o.GOMsN2wZxHDC');
