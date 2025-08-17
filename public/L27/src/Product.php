<?php

namespace App\L27\src;

class Product
{
    private ?int $id = null;
    private ?string $title = null;
    private ?int $price = null;

    public static function fromArray(array $productData): Product
    {
        ['title' => $title, 'price' => $price] = $productData;
        $product = new Product();
        $product->setTitle($title);
        $product->setPrice((int) $price);
        return $product;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setPrice(int $price): void
    {
        $this->price = $price;
    }

    public function exists(): bool
    {
        return !is_null($this->getId());
    }
}
