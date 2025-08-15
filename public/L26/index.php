<?php

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;

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
$app->add(MethodOverrideMiddleware::class);

$users = [
    ['name' => 'admin', 'passwordDigest' => password_hash('secret', PASSWORD_DEFAULT)],
    ['name' => 'mike', 'passwordDigest' => password_hash('superpass', PASSWORD_DEFAULT)],
    ['name' => 'kate', 'passwordDigest' => password_hash('strongpass', PASSWORD_DEFAULT)]
];

// BEGIN (write your solution here)
$app->get('/', function ($request, $response) use ($container) {
    $user = $_SESSION['user'] ?? ['name' => '', 'auth' => false];
    $messages = $this->get('flash')->getMessages();
    $params['user'] = $user;
    $params['flash'] = $messages;

    return $container
        ->get('renderer')
        ->render($response, 'index.phtml', $params);
});

$app->post('/session', function ($request, $response) use ($container, $users) {
    $credentials = $request->getParsedBodyParam('user') ?? [];
    $name = $credentials['name'] ?? '';
    $password = $credentials['password'] ?? '';
    $sessionUser = $_SESSION['user'] ?? ['name' => '', 'auth' => false];
    $foundUser = null;

    foreach ($users as $u) {
        if ($u['name'] === $name) {
            $foundUser = $u;
            break;
        }
    }

    if ($foundUser && password_verify($password, $foundUser['passwordDigest'])) {
        $sessionUser['auth'] = true;
        $sessionUser['name'] = $name;
        $_SESSION['user'] = $sessionUser;
        return $response->withRedirect('/');
    }

    $container->get('flash')->addMessage('error', 'Wrong password or name.');

    return $response->withRedirect('/');
});

$app->delete('/session', function ($request, $response) use ($container) {
    unset($_SESSION['user']);
    session_destroy();
    return $response->withRedirect('/');
});
// END

$app->run();
