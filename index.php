<?php

// Подключение автозагрузки через composer
require __DIR__ . '/vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;
use Src\Validator;
use Src\Car;
use Src\CarRepository;
use Src\CarValidator;

session_start();


//$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];

//$storage = function () {
//    $dir = __DIR__ . '/files';
//    $path = $dir . '/users_dp';
//    if (!is_dir($dir)) {
//        mkdir($dir, 0777, true);
//    }
//    if (!file_exists($path)) {
//        $data = [];
//    } else {
//        $content = file_get_contents($path);
//        $data = json_decode($content, true) ?? [];
//    }
//    return ['data' => $data, 'path' => $path];
//};


$container = new Container();
$container->set(\PDO::class, function () {
    $conn = new \PDO('sqlite:database.sqlite');
    $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    return $conn;
});

$initFilePath = implode('/', [dirname(__DIR__), 'phpMVC/init.sql']);
$initSql = file_get_contents($initFilePath);
$container->get(\PDO::class)->exec($initSql);

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


$app->get('/', function ($request, $response) use ($container) {
    $params['router'] = $container->get('router');
    $user['email'] = '';
    $params['user'] = $user;
    return $container
        ->get('renderer')
        ->render($response, 'users/login.phtml', $params);
})->setName('login');

$app->post('/', function ($request, $response) use ($container) {
   $user = $request->getParsedBodyParam('user') ?? '';
   $email = $user['email'];
   $usersUrl = $container->get('router')->urlFor('users');

   if ($email !== '' && str_contains($email, '@')) {
       $_SESSION['user'][] = $user['email'];
       return $response->withRedirect($usersUrl);
   }

   $errors['email'] = 'Username must be valid email';
   $params['errors'] = $errors;
   $params['router'] = $container->get('router');
   $params['user'] = $user;
    return $container
        ->get('renderer')
        ->render($response, 'users/login.phtml', $params);
})->setName('login');

$app->delete('/', function ($request, $response) use ($container) {
    $loginUrl = $container->get('router')->urlFor('login');
    setcookie('user', '', time() - 3600, '/');
    $_SESSION = [];
    session_destroy();
    return $response->withRedirect($loginUrl);
});

$app->get('/users', function ($request, $response) {
    $router = $this->get('router');
    $data = json_decode($_SESSION['users'] ?? json_encode([]), true);
    $term = $request->getQueryParam('term') ?? '';
    $messages = $this->get('flash')->getMessages();
    if ($term) {
        $params['users'] = collect($data)
            ->filter(fn($item) => str_contains($item['nickname'], $term))
            ->all();
    } else {
        $params['users'] = $data;
    }
    $params['term'] = $term;
    $params['router'] = $router;
    $params['flash'] = $messages;
    return $this
        ->get('renderer')
        ->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->get('/users/new', function($request, $response) use ($container) {
    $params = [
        'user' => ['id' => '', 'nickname' => '', 'email' => ''],
        'errors' => [],
        'router' => $container->get('router')
    ];
    return $container->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('users.new');

$app->post('/users', function($request, $response) use ($container) {
    $data = json_decode($_SESSION['users'] ?? json_encode([]), true);
    $router = $this->get('router');
    $validator = new Validator();
    $user = $request->getParsedBodyParam('user');
    $errors = $validator->validate($user);
    if (count($errors) === 0) {
        if (last($data)) {
            $user['id'] = last($data)['id'] + 1;
        } else {
            $user['id'] = 1;
        }
        $data [] = $user;
        $json = json_encode($data);
        $_SESSION['users'] = $json;
        $container->get('flash')->addMessage('success', 'User was added successfully');
        return $response
            ->withRedirect($router->urlFor('users'), 302);
    }
    $params = ['user' => $user, 'errors' => $errors, 'router' => $router];
    $response =  $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('users.store');

$app->get('/users/{id}', function ($request, $response, $args) {
    $data = json_decode($_SESSION['users'] ?? json_encode([]), true);
    $id = $args['id'];
    $router = $this->get('router');
    $collection = collect($data);
    $user = $collection->firstWhere('id', (string) $id);
    if (empty($user)) {
        return $response->withStatus(404);
    }
    $params = ['id' => $args['id'], 'user' => $user, 'router' => $router];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('users.id');

$app->get('/users/{id}/edit', function ($request, $response, $args) use ($container) {
    $data = json_decode($_SESSION['users'] ?? json_encode([]), true);
    $id = (string) $args['id'];
    $collection = collect($data);
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

$app->patch('/users/{id}', function ($request, $response, $args) use ($container) {
    $data = json_decode($_SESSION['users'] ?? json_encode([]), true);
    $idToUpdate = (string) $args['id'];
    $newUserData = $request->getParsedBodyParam('user');
    $validator = new Validator();
    $errors = $validator->validate($newUserData);
    $collection = collect($data);

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
        $json = json_encode($updated);
        $_SESSION['users'] = $json;
        $container->get('flash')->addMessage('success', 'User was edited successfully');
        return $response
            ->withRedirect(
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

$app->delete('/users/{id}', function ($request, $response, $args) use ($container) {
    $data = json_decode($_SESSION['users'] ?? json_encode([]), true);
    $id = (string) $args['id'];
    $collection = collect($data);
    $user = $collection->firstWhere('id', $id);
    if (!$user) {
        return $response->withStatus(404);
    }
    $updated = $collection->reject(function ($item) use ($id) {
        return (string) $item['id'] === $id;
    })->all();
    $json = json_encode($updated);
    $_SESSION['users'] = $json;
    $container->get('flash')->addMessage('success', 'User was deleted successfully');
    return $response
        ->withHeader('Set-Cookie', 'users=' . rawurlencode($json) . '; Path=/; HttpOnly')
        ->withRedirect(
        $container->get('router')->urlFor('users.store')
    );
});

$app->get('/cars', function ($request, $response) use ($container) {
    $carRepository = $container->get(CarRepository::class);
    $cars = $carRepository->getEntities();

    $messages = $container->get('flash');

    $params = [
        'cars' => $cars,
        'flash' => $messages
    ];

    return $container
        ->get('renderer')
        ->render($response, 'cars/index.phtml', $params);
})->setName('cars.index');

$app->get('/cars/new', function ($request, $response) use ($container) {
    $params = [
        'car' => new Car(),
        'errors' => []
    ];

    return $container
        ->get('renderer')
        ->render($response, 'cars/new.phtml', $params);
})->setName('cars.create');

$app->get('/cars/{id}', function ($request, $response, $arg) use ($container) {
    $id = $arg['id'];

    $carsRepository = $container->get(CarRepository::class);
    $car = $carsRepository->find($id);

    if (is_null($car)) {
        return $response
            ->write('Page not found')
            ->withStatus(404);
    }

    $messages = $container->get('flash');

    $params = [
        'car' => $car,
        'flash' => $messages
    ];

    return $container
        ->get('renderer')
        ->render($response, 'cars/show.phtml', $params);
})->setName('cars.show');

$app->get('/cars/{id}/edit', function ($request, $response, $args) use ($container) {
    $id = $args['id'];

    $carRepository = $container->get(CarRepository::class);
    $car = $carRepository->find($id);

    if (!$car) {
        throw new Slim\Exception\HttpNotFoundException($request);
    }

    $params = [
        'car' => $car,
        'errors' => []
    ];

    return $this->get('renderer')->render($response, 'cars/edit.phtml', $params);
});

$app->put('/cars/{id}', function ($request, $response, $args) use ($container) {
    $id = (int) $args['id'];

    $carRepository = $container->get(CarRepository::class);
    $car = $carRepository->find($id);

    if (!$car) {
        throw new Slim\Exception\HttpNotFoundException($request);
    }

    $carData = $request->getParsedBodyParam('car');

    $validator = new CarValidator();
    $errors = $validator->validate($carData);

    if (count($errors) === 0) {
        $car = Car::fromArray(['make' => $carData['make'], 'model' => $carData['model']]);
        $car->setId($id);
        $carRepository->save($car);
        $container->get('flash')->addMessage('success', 'Car was updated successfully');
        return $response->withRedirect($container->get('router')->urlFor('cars.index'));
    }
    $params = [
        'car' => $carData,
        'errors' => $errors
    ];
    return $container
        ->get('renderer')
        ->render($response->withStatus(422), 'cars/edit.phtml', $params);
})->setName('cars.store');

$app->post('/cars', function ($request, $response) use ($container) {
    $carRepository = $container->get(CarRepository::class);
    $carData = $request->getParsedBodyParam('car');

    $validator = new CarValidator();
    $errors = $validator->validate($carData);

    if (count($errors) === 0) {
        $car = Car::fromArray(['make' => $carData['make'], 'model' => $carData['model']]);
        $carRepository->save($car);
        $container->get('flash')->addMessage('success', 'Car was added successfully');
        return $response->withRedirect($container->get('router')->urlFor('cars.index'));
    }
    $params = [
        'car' => $carData,
        'errors' => $errors
    ];
    return $container
        ->get('renderer')
        ->render($response->withStatus(422), 'cars/new.phtml', $params);
})->setName('cars.store');

$app->delete('/cars/{id}', function ($request, $response, $args) use  ($container) {
    $id = $args['id'];
    $carRepository = $container->get(CarRepository::class);
    $car = $carRepository->find($id);

    if (!is_null($car)) {
        $carRepository->delete($car);

        $container->get('flash')->addMessage('success', 'Car was deleted successfully');
        return $response->withRedirect($container->get('router')->urlFor('cars.index'));
    }
    return $response
        ->withStatus(404);
});

$app->run();
