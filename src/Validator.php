<?php

namespace Src;

class Validator implements ValidatorInterface
{
    public function validate(array $user)
    {
        // BEGIN (write your solution here)
        $errors = [];
        if (mb_strlen($user['nickname']) <= 4) {
            $errors['nickname'] = "Nickname must be grater than 4 character";
        }
        if (!str_contains($user['email'], '@')) {
            $errors['email'] = "Email is incorrect";
        }
        return $errors;
        // END
    }
}
