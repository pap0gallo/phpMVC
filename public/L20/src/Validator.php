<?php

namespace App\L20\src;

require __DIR__ . '/../../../vendor/autoload.php';
class Validator
{
    public function validate(array $post)
    {
        $errors = [];
        if ($post['name'] === '') {
            $errors['name'] = "Can't be blank";
        }

        if ($post['body'] === '') {
            $errors['body'] = "Can't be blank";
        }
        return $errors;
    }
}
