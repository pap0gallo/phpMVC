<?php

use Slim\Factory\AppFactory;
use DI\Container;

use function Symfony\Component\String\s;

require __DIR__ . '/../../vendor/autoload.php';

$users = App\L13\src\Generator::generate(100);

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/templates');
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
});

// BEGIN (write your solution here)
$app->get('/users', function ($request, $response) use ($users) {
    $term = $request->getQueryParam('term') ?? '';
    $output = collect($users)->filter(
        fn($user) => empty($term) || s($user['firstName'])
                ->ignoreCase()
                ->startsWith($term)
    )->all();
    $params = ['term' => $term, 'users' => $output];
    return $this
        ->get('renderer')
        ->render($response, 'users/index.phtml', $params);
});
// END

$app->run();
