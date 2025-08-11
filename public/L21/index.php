<?php

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;

require __DIR__ . '/../../vendor/autoload.php';

$container = new Container();
$container->set('renderer', function () {
    return new Slim\Views\PhpRenderer(__DIR__ . '/templates');
});
$container->set('flash', function () {
    return new Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$repo = new App\L21\src\PostRepository();
$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
});

$app->get('/posts', function ($request, $response) use ($repo) {
    $flash = $this->get('flash')->getMessages();

    $params = [
        'flash' => $flash,
        'posts' => $repo->all()
    ];
    return $this->get('renderer')->render($response, 'posts/index.phtml', $params);
})->setName('posts');

$app->get('/posts/new', function ($request, $response) use ($repo) {
    $params = [
        'postData' => [],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'posts/new.phtml', $params);
});

$app->post('/posts', function ($request, $response) use ($repo, $router) {
    $postData = $request->getParsedBodyParam('post');

    $validator = new App\L21\src\Validator();
    $errors = $validator->validate($postData);

    if (count($errors) === 0) {
        $id = $repo->save($postData);
        $this->get('flash')->addMessage('success', 'Post has been created');
        return $response->withHeader('X-ID', $id)
            ->withRedirect($router->urlFor('posts'));
    }

    $params = [
        'postData' => $postData,
        'errors' => $errors
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'posts/new.phtml', $params);
});

// BEGIN (write your solution here)
$app->get('/posts/{id}/edit', function ($request, $response, $args) use ($container, $repo) {
    $id = (string) $args['id'];

    $postData = $repo->find($id);
    if (!$postData) {
        return $response->withStatus(404);
    }

    return $container->get('renderer')->render($response, 'posts/edit.phtml', [
        'postData' => $postData,
        'errors' => [],
        'flash' => $container->get('flash')->getMessages(),
    ]);
})->setName('posts.edit');

$app->patch('/posts/{id}', function ($request, $response, $args) use ($container, $router, $repo) {

    $idToUpdate = (string) $args['id'];
    $newPostData = $request->getParsedBodyParam('post');
    $postData = $repo->find($idToUpdate);

    $validator = new App\L21\src\Validator();
    $errors = $validator->validate($newPostData);

    if (empty($postData)) {
        return $response->withStatus(404);
    }

    if (empty($errors)) {
        $repo->destroy($idToUpdate);
        $postData = array_merge($postData, $newPostData);
        $repo->save($postData);

        $container->get('flash')->addMessage('success', 'Post has been updated');
        return $response->withHeader('X-ID', $idToUpdate)->withRedirect(
            $router->urlFor('posts')
        );
    }

    $response = $response->withStatus(422);
    return $container->get('renderer')->render($response, 'posts/edit.phtml', [
        'errors' => $errors,
        'postData' => $postData
    ]);
});
// END

$app->run();
