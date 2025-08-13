<?php

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;

require __DIR__ . '/../../vendor/autoload.php';

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/templates');
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $cart = json_decode($request->getCookieParam('cart', json_encode([])), true);
    $params = [
        'cart' => $cart
    ];
    return $this->get('renderer')->render($response, 'index.phtml', $params);
});

// BEGIN (write your solution here)
$app->post('/cart-items', function ($request, $response) {
    $cart = json_decode($request->getCookieParam('cart', json_encode([])), true);
    $newItem = $request->getParsedBodyParam('item');
    $newItem['count'] = 1;

    $updated = $cart;
    $found = false;
    foreach ($updated as &$item) {
        if ($item['id'] === $newItem['id']) {
            $item['count'] += 1;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $updated[] = $newItem;
    }

    return $this->get('renderer')->render(
        $response->withHeader('Set-Cookie', 'cart=' . json_encode($updated) . '; Path=/; HttpOnly'),
        'index.phtml',
        ['cart' => $updated]
    );
});

$app->delete('/cart-items', function ($request, $response) {
    $updated = [];
    return $this->get('renderer')->render(
        $response->withHeader('Set-Cookie', 'cart=' . json_encode($updated) . '; Path=/; HttpOnly'),
        'index.phtml',
        ['cart' => $updated]
    );
});
// END

$app->run();
