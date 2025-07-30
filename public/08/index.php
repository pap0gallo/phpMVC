<?php

use Faker\Factory;
use Slim\Factory\AppFactory;

require __DIR__ . '/../../vendor/autoload.php';

$faker = Factory::create();
$faker->seed(1234);

$domains = [];
for ($i = 0; $i < 10; $i++) {
    $domains[] = $faker->domainName;
}

$phones = [];
for ($i = 0; $i < 10; $i++) {
    $phones[] = $faker->phoneNumber;
}

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $response->write('go to the /phones or /domains');
});

// BEGIN (write your solution here)
$app->get('/phones', function ($request, $response) use ($phones) {
    $json = json_encode($phones, JSON_PRETTY_PRINT);
    $response->write($json);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/domains', function ($request, $response) use ($domains) {
    $json = json_encode($domains, JSON_PRETTY_PRINT);
    $response->write($json);
    return $response->withHeader('Content-Type', 'application/json');
});
// END

$app->run();
