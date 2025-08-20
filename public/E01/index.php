<?php

use App\E01\src\Post;
use App\E01\src\PostsRepository;
use App\E01\src\Validator;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Tuupola\Middleware\HttpBasicAuthentication;
use Tuupola\Middleware\HttpBasicAuthentication\PdoAuthenticator;

require __DIR__ . '/../../vendor/autoload.php';

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new Slim\Views\PhpRenderer(__DIR__ . '/templates');
});

$container->set('flash', function () {
    return new Slim\Flash\Messages();
});

$container->set(PDO::class, function () {
    $host = 'localhost';
    $port = 5432;            // стандартный порт PostgreSQL
    $dbname = 'a.khodunov';  // имя базы
    $user = 'a.khodunov';    // имя пользователя
    $password = '';           // пароль, если не установлен

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    $conn = new \PDO($dsn, $user, $password);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $conn;
});


$app = AppFactory::createFromContainer($container);
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$repo = $container->get(PostsRepository::class);

// BEGIN (write your solution here)
$pdo = $container->get(\PDO::class);

$basicAuthMiddleware = new HttpBasicAuthentication([
    'realm' => 'Protected',
    'authenticator' => new PdoAuthenticator([
        'pdo' => $pdo,
        'table' => 'users',
        'user' => 'nickname',
        'hash' => 'password_hash'
    ]),
    'before' => function ($request, $arguments) {
        return $request->withAttribute("nickname", $arguments["user"]);
    }
]);
// END

$app->get('/', function (Request $request, Response $response): Response {
    return $this->get('renderer')->render($response, 'index.phtml');
})->setName('root');

// BEGIN (write your solution here)
$app->get('/posts/new', function (Request $request, Response $response) use ($container, $repo): Response {
    $nickname = $request->getAttribute("nickname");
    $params = [
        'errors' => [],
        'post' => Post::fromArray(['author' => $nickname])
    ];
    return $container->get('renderer')->render($response, 'posts/new.phtml', $params);
})->setName('posts.new')->add($basicAuthMiddleware);

$app->post('/posts', function (Request $request, Response $response)use ($container, $repo, $router): Response {
    $nickname = $request->getAttribute("nickname");
    $postData = $request->getParsedBodyParam('post');
    $post = Post::fromArray([
        'author' => $nickname,
        'title' => $postData['title'],
        'body' => $postData['body']
    ]);
    $validator = new Validator();
    $errors = $validator->validate($post);

    if (count($errors) === 0) {
        $repo->save($post);
        $container->get('flash')->addMessage('success', 'Post has been created successfully');
        return $response->withRedirect($router->urlFor('posts.index'));
    }
    $params = [
        'post' => $post,
        'errors' => $errors
    ];
    return $container->get('renderer')->render($response->withStatus(422), 'posts/new.phtml', $params);
})->setName('posts.create')->add($basicAuthMiddleware);
// END

$app->get('/posts', function ($request, $response) use ($repo) {
    $flash = $this->get('flash')->getMessages();

    $params = [
        'flash' => $flash,
        'posts' => $repo->getEntities()
    ];

    return $this->get('renderer')->render($response, 'posts/index.phtml', $params);
})->setName('posts.index');

$app->run();
