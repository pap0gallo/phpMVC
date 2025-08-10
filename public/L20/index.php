<?php

use Slim\Factory\AppFactory;
use DI\Container;

require __DIR__ . '/../../vendor/autoload.php';

$container = new Container();
$container->set('renderer', function () {
    return new Slim\Views\PhpRenderer(__DIR__ . '/templates');
});
$container->set('flash', function () {
    return new Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$repo = new App\L20\src\PostRepository();
$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) use ($container){
    return $container
        ->get('renderer')
        ->render($response, 'index.phtml');
});

$app->get('/posts', function ($request, $response) use ($container, $repo) {
    $flash = $container
        ->get('flash')
        ->getMessages();

    $params = [
        'flash' => $flash,
        'posts' => $repo->all()
    ];
    return $container
        ->get('renderer')
        ->render($response, 'posts/index.phtml', $params);
})->setName('posts');

// BEGIN (write your solution here)
$app->get('/posts/new', function ($request, $response) use ($container, $router) {
    $params = ['post' => ['id' => '', 'name' => '', 'body' => ''],
        'router' => $router,
        'errors' => []];

    return $container
        ->get('renderer')
        ->render($response, 'posts/new.phtml', $params);
})->setName('posts.new');

$app->post('/posts', function ($request, $response) use ($container, $router, $repo) {
    $post = $request->getParsedBodyParam('post');
    $validator = new \App\L20\src\Validator();
    $errors = $validator->validate($post);

    if (count($errors) === 0) {
        $repo->save($post);
        $container
            ->get('flash')
            ->addMessage('success', 'Post has been created');
        return $response->withRedirect($router->urlFor('posts'), 302);
    }

    $params = ['post' => $post, 'errors' => $errors, 'router' => $router];
    $response = $response->withStatus(422);
    return $container
        ->get('renderer')
        ->render($response, 'posts/new.phtml', $params);
})->setName('posts.store');
// END

$app->run();
