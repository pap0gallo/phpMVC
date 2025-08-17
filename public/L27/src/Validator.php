<?php

namespace App\L27\src;

class Validator
{
    public function validate(array $productData): array
    {
        $errors = [];
        if (empty($productData['title'])) {
            $errors['title'] = "Can't be blank";
        }

        if (!$this->isNumber($productData['price'])) {
            $errors['price'] = "Should be a number";
        } elseif ($this->isNegative($productData['price'])) {
            $errors['price'] = "Can't be negative";
        }

        return $errors;
    }

    private function isNumber($value): bool
    {
        return is_numeric($value);
    }

    private function isNegative(int $value): bool
    {
        return (int) $value < 0;
    }
}
