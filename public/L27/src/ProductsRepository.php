<?php

namespace App\L27\src;

use App\L27\src\ProductsRepositoryInterface;
use App\L27\src\Product;

class ProductsRepository implements ProductsRepositoryInterface
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getEntities(): array
    {
        $products = [];
        $sql = "SELECT * FROM products";
        $stmt = $this->conn->query($sql);

        while ($row = $stmt->fetch()) {
            $product = Product::fromArray($row);
            $product->setId($row['id']);
            $products[] = $product;
        }

        return $products;
    }

    // BEGIN (write your solution here)
    public function find(int $id): ?Product
    {
        $sql = "SELECT * FROM products WHERE id =?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);

        if ($row = $stmt->fetch()) {
            $product = Product::fromArray(['title' => $row['title'], 'price' => $row['price']]);
            $product->setId($row['id']);
            return $product;
        }

        return null;
    }

    public function save(Product $product): void
    {
        if ($product->exists()) {
            $this->update($product);
        } else {
            $this->create($product);
        }
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM products WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
    }

    private function update(Product $product): void
    {
        $sql = "UPDATE products SET title = :title, price = :price WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $id = $product->getId();
        $title = $product->getTitle();
        $price = $product->getPrice();

        $stmt->bindParam('id', $id);
        $stmt->bindParam('title', $title);
        $stmt->bindParam('price', $price);
        $stmt->execute();
    }

    private function create(Product $product): void
    {
        $sql = "INSERT INTO products (title, price) VALUES (:title, :price)";
        $stmt = $this->conn->prepare($sql);
        $title = $product->getTitle();
        $price = $product->getPrice();

        $stmt->bindParam('title', $title);
        $stmt->bindParam('price', $price);
        $stmt->execute();
        $id = (int) $this->conn->lastInsertId();
        $product->setId($id);
    }
    // END
}
