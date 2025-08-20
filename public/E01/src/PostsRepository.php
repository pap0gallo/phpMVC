<?php

namespace App\E01\src;

require __DIR__ . '/../../../vendor/autoload.php';

use PDO;
use App\E01\src\Post;

readonly class PostsRepository
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @return Post[]
     */
    public function getEntities(): array
    {
        $posts = [];
        $sql = "SELECT * FROM posts";
        $stmt = $this->conn->query($sql);

        while ($row = $stmt->fetch()) {
            $post = Post::fromArray($row);
            $posts[] = $post;
        }

        return $posts;
    }

    public function save(Post $post): void
    {
        $this->create($post);
    }

    private function create(Post $post): void
    {
        $sql = "INSERT INTO posts (title, body, author) VALUES (:title, :body, :author)";
        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':title', $post->title);
        $stmt->bindParam(':body', $post->body);
        $stmt->bindParam(':author', $post->author);

        $stmt->execute();
    }
}
