<?php

namespace App\L19\src;

require __DIR__ . '/../../../vendor/autoload.php';

class PostRepository
{
    private $posts;
    public function __construct()
    {
        $this->posts = Generator::generate(100);
    }

    public function all()
    {
        return $this->posts;
    }

    public function find(string $id)
    {
        return collect($this->posts)->firstWhere('id', $id);
    }
}
