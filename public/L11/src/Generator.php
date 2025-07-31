<?php

namespace App\L11\src;

require __DIR__ . '/../../../vendor/autoload.php';

use Faker\Factory as FakerFactory;

class Generator
{
    public static function generate($count)
    {
        $range = range(1, $count - 2);
        $numbers = collect($range)->shuffle(1)->toArray();

        $faker = FakerFactory::create();
        $faker->seed(1234);
        $users = [];
        for ($i = 0; $i < $count - 2; $i++) {
            $users[] = [
                'id' => $numbers[$i],
                'firstName' => $faker->firstName,
                'lastName' => $faker->lastName,
                'email' => $faker->email
            ];
        }

        $users[] = [
            'id' => 99,
            'firstName' => $faker->firstName,
            'lastName' => $faker->lastName,
            'email' => $faker->email
        ];

        $users[] = [
            'id' => 100,
            'firstName' => $faker->firstName,
            'lastName' => $faker->lastName,
            'email' => $faker->email
        ];

        return $users;
    }
}
