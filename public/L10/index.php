<?php

use Slim\Factory\AppFactory;

require __DIR__ . '/../../vendor/autoload.php';

$companies = App\L10\Generator::generate(100);

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response, $args) {
    return $response->write('open something like (you can change id): /companies/5');
});

// BEGIN (write your solution here)
$app->get('/companies/{id}', function ($request, $response, $args) use ($companies){
    $id = $args['id'];
    $collection = collect($companies);
    $company = $collection->firstWhere('id', $id);
    if (!is_null($company)) {
        $company['id'] = (string) $id;
        $json = json_encode($company, JSON_PRETTY_PRINT);
        return $response
            ->write($json)
            ->withHeader('Content-Type', 'application/json');
    } else {
        return $response->withStatus(404, 'Page not found');
    }
});
// END

$app->run();
