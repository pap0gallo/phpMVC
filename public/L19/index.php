<?php

use Slim\Factory\AppFactory;
use DI\Container;

require __DIR__ . '/../../vendor/autoload.php';

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/templates');
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$repo = new App\L19\src\PostRepository();

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
});

// BEGIN (write your solution here)
$app->get('/posts', function ($request, $response) use ($repo) {
    $posts = $repo->all();
    $page = (int) $request->getQueryParam('page', 1);
    $per = 5;
    $offset = ($page - 1) * $per;
    $slice = array_slice($posts, $offset, $per);
    $params['page'] = $page;
    $params['slice'] = $slice;
    return $this
        ->get('renderer')
        ->render($response, 'posts/index.phtml', $params);
})->setName('posts');

$app->get('/posts/{id}', function ($request, $response, $args) use ($repo) {
    $id = $args['id'];
    $post = $repo->find($id);
    if (!$post) {
        return $response->withStatus(404, 'Page not found');
    }
    $params['post'] = $post;
    return $this
        ->get('renderer')
        ->render($response, 'posts/show.phtml', $params);
})->setName('posts.show');
// END

$app->run();
