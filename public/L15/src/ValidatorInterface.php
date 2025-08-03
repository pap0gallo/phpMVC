<?php

namespace App\L15\src;

require __DIR__ . '/../../../vendor/autoload.php';

interface ValidatorInterface
{
    // Return array of errors, or empty array if no errors
    public function validate(array $data);
}
