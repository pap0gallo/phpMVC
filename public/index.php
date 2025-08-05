<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

session_start();

$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$container->set('router', function () use ($app) {
    return $app->getRouteCollector()->getRouteParser();
});

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');
})->setName('home');

$app->get('/users', function ($request, $response) {
    $router = $this->get('router');
    $dir = __DIR__ . '/../files';
    $path = $dir . '/users_dp';
    $content = file_get_contents($path);
    $users = json_decode($content, JSON_PRETTY_PRINT);
    $term = $request->getQueryParam('term') ?? '';
    $messages = $this->get('flash')->getMessages();
    if ($term) {
        $params['users'] = collect($users)
            ->filter(fn($item) => str_contains($item['nickname'], $term))
            ->all();
    } else {
        $params['users'] = $users;
    }
    $params['term'] = $term;
    $params['router'] = $router;
    $params['flash'] = $messages;
    return $this
        ->get('renderer')
        ->render($response, 'users/index.phtml', $params);
})->setName('users');

//$app->post('/users', function ($request, $response) {
//    return $response->withStatus(302);
//});

//$app->get('/courses/{id}', function ($request, $response, array $args) {
//    $id = $args['id'];
//    return $response->write("Course id: {$id}");
//})->setName('course.show');

$app->get('/users/new', function($request, $response) {
    $router = $this->get('router');
    $params = [
        'user' => ['nickname' => '', 'email' => ''],
        'errors' => [],
        'router' => $router
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('users.new');

$app->post('/users', function($request, $response) {
    $router = $this->get('router');
    $dir = __DIR__ . '/../files';
    $path = $dir . '/users_dp';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $user = $request->getParsedBodyParam('user');
    if (!file_exists($path)) {
        $data = [];
    } else {
        $content = file_get_contents($path);
        $data = json_decode($content, true) ?? [];
    }

    $data[array_key_last($data) + 1 ?? 1] = $user;
    $json = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents($path, $json);
    $this->get('flash')->addMessage('success', 'User was added successfully');
    return $response->withRedirect($router->urlFor('users'), 302);
})->setName('users.store');

$app->get('/users/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    $router = $this->get('router');
    $dir = __DIR__ . '/../files';
    $path = $dir . '/users_dp';
    $content = file_get_contents($path);
    $data = json_decode($content, true) ?? [];
    if (!array_key_exists($id, $data)) {
        return $response->withStatus(404);
    }
    $params = ['id' => $args['id'], 'user' => $data[$id], 'router' => $router];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('users.id');

$app->run();
