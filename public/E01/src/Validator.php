<?php

namespace App\E01\src;

require __DIR__ . '/../../../vendor/autoload.php';

class Validator
{
    public function validate(Post $post): array
    {
        $errors = [];
        if (empty($post->title)) {
            $errors['title'] = "Can't be blank";
        }
        if (empty($post->body)) {
            $errors['body'] = "Can't be blank";
        }
        if (empty($post->author)) {
            $errors['author'] = "Can't be blank";
        }

        return $errors;
    }
}
