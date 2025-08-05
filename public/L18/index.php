<?php

use DI\Container;
use Slim\Factory\AppFactory;

require __DIR__ . '/../../vendor/autoload.php';

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

// BEGIN (write your solution here)
$app->get('/', function ($request, $response) {
    $messages = $this->get('flash')->getMessages();
    $params['flash'] = $messages;

    return $this
        ->get('renderer')
        ->render($response, 'index.phtml', $params);
});

$app->post('/courses', function ($request, $response) {
    $this->get('flash')->addMessage('success', 'Course Added');
    return $response->withRedirect('/', 302);
});
// END

$app->run();
