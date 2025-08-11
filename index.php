<?php

// Подключение автозагрузки через composer
require __DIR__ . '/vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;
use Src\Validator;

session_start();

//$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];

$storage = function () {
    $dir = __DIR__ . '/files';
    $path = $dir . '/users_dp';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    if (!file_exists($path)) {
        $data = [];
    } else {
        $content = file_get_contents($path);
        $data = json_decode($content, true) ?? [];
    }
    return ['data' => $data, 'path' => $path];
};

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});
$app = AppFactory::createFromContainer($container);
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);
$container->set('router', function () use ($app) {
    return $app->getRouteCollector()->getRouteParser();
});


$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim! <br> Go to <a href="http://localhost:8080/users">Users</a>');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');
})->setName('home');

$app->get('/users', function ($request, $response) use ($storage) {
    $repo = $storage();
    $router = $this->get('router');
    $users = $repo['data'];
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
        'user' => ['id' => '', 'nickname' => '', 'email' => ''],
        'errors' => [],
        'router' => $router
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('users.new');

$app->post('/users', function($request, $response) use ($storage) {
    $repo = $storage();
    $router = $this->get('router');
    $validator = new Validator();
    $user = $request->getParsedBodyParam('user');
    $errors = $validator->validate($user);
    if (count($errors) === 0) {
        $path = $repo['path'];
        $data = $repo['data'];
        if (last($data)) {
            $user['id'] = last($data)['id'] + 1;
        } else {
            $user['id'] = 1;
        }
        //$user['id'] = last($data)['id'] + 1 ?? 1;
        //$data[array_key_last($data) + 1 ?? 1] = $user;
        $data [] = $user;
        $json = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($path, $json);
        $this->get('flash')->addMessage('success', 'User was added successfully');
        return $response->withRedirect($router->urlFor('users'), 302);
    }
    $params = ['user' => $user, 'errors' => $errors, 'router' => $router];
    $response =  $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('users.store');

$app->get('/users/{id}', function ($request, $response, $args) use ($storage) {
    $repo = $storage();
    $id = $args['id'];
    $router = $this->get('router');
    $collection = collect($repo['data']);
    $user = $collection->firstWhere('id', (string) $id);
    if (empty($user)) {
        return $response->withStatus(404);
    }
    $params = ['id' => $args['id'], 'user' => $user, 'router' => $router];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('users.id');

$app->get('/users/{id}/edit', function ($request, $response, $args) use ($container, $storage) {
    $repo = $storage();
    $id = (string) $args['id'];
    $collection = collect($repo['data']);
    $user = $collection->firstWhere('id', $id);
    if (!$user) {
        return $response->withStatus(404);
    }

    return $container->get('renderer')->render($response, 'users/edit.phtml', [
        'user'   => $user,
        'errors' => [],
        'flash'  => $container->get('flash')->getMessages(),
        'router' => $container->get('router')
    ]);
})->setName('users.edit');

$app->patch('/users/{id}', function ($request, $response, $args) use ($container, $storage) {
    $repo = $storage();
    $path = $repo['path'];
    $idToUpdate = (string) $args['id'];
    $newUserData = $request->getParsedBodyParam('user');
    $validator = new Validator();
    $errors = $validator->validate($newUserData);
    $collection = collect($repo['data']);

    // Проверка существования пользователя
    $userExists = $collection->contains(fn($item) => (string)$item['id'] === $idToUpdate);
    if (!$userExists) {
        return $response->withStatus(404);
    }

    // Если ошибок нет — обновляем и сохраняем
    if (empty($errors)) {
        $updated = $collection->map(function ($item) use ($idToUpdate, $newUserData) {
            if ((string)$item['id'] === $idToUpdate) {
                return array_merge($item, [
                    'nickname' => $newUserData['nickname'],
                    'email' => $newUserData['email']
                ]);
            }
            return $item;
        });

        file_put_contents($path, json_encode($updated->all(), JSON_PRETTY_PRINT));

        $container->get('flash')->addMessage('success', 'User was edited successfully');
        return $response->withRedirect(
            $container->get('router')->urlFor('users.edit', ['id' => $idToUpdate])
        );
    }

    // Если есть ошибки
    $response = $response->withStatus(422);
    return $container->get('renderer')->render($response, 'users/edit.phtml', [
        'errors' => $errors,
        'user' => $newUserData

    ]);
});

$app->delete('/users/{id}', function ($request, $response, $args) use ($container, $storage) {
    $repo = $storage();
    $id = (string) $args['id'];
    $path = $repo['path'];
    $collection = collect($repo['data']);
    $user = $collection->firstWhere('id', $id);
    if (!$user) {
        return $response->withStatus(404);
    }
    $updated = $collection->reject(function ($item) use ($id) {
        return (string) $item['id'] === $id;
    })->all();
    file_put_contents($path, json_encode($updated, JSON_PRETTY_PRINT));
    $container->get('flash')->addMessage('success', 'User was deleted successfully');
    return $response->withRedirect(
        $container->get('router')->urlFor('users.store')
    );
});

$app->run();
