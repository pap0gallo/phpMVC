<?php

namespace App\L27\src;

use App\L27\src\Product;

interface ProductsRepositoryInterface
{
    public function getEntities(): array;
    public function find(int $id): ?Product;
    public function delete(int $id): void;
    public function save(Product $product): void;
}
