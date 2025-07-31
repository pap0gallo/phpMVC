<?php

use Slim\Factory\AppFactory;

require __DIR__ . '/../../vendor/autoload.php';

$companies = \App\L09\src\Generator::generate(100);

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $response->write('go to the /companies');
});

// BEGIN (write your solution here)
$app->get('/companies', function ($request, $response) use ($companies) {

    $page = $request->getQueryParam('page', 1) ?? 1;
    $per = $request->getQueryParam('per', 5) ?? 5;
    $offset = ($page - 1) * $per;

    $slice = array_slice($companies, $offset, $per);
    $json = json_encode($slice, JSON_PRETTY_PRINT);
    return $response
        ->write($json)
        ->withHeader('Content-Type', 'application/json');
});
// END

$app->run();
