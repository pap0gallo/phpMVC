<?php

namespace App\E01\src;

require __DIR__ . '/../../../vendor/autoload.php';

class Post
{
    public function __construct(
        public ?int $id = null,
        public ?string $title = null,
        public ?string $body = null,
        public ?string $author = null,
    ) {
    }

    public static function fromArray(array $postData): self
    {
        $post = new self(
            id: $postData['id'] ?? null,
            title: $postData['title'] ?? null,
            body: $postData['body'] ?? null,
            author: $postData['author'] ?? null,
        );

        return $post;
    }
}
