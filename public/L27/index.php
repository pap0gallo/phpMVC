<?php

use App\L27\src\Product;
use App\L27\src\ProductsRepository;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;

require __DIR__ . '/../../vendor/autoload.php';

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new Slim\Views\PhpRenderer(__DIR__ . '/templates');
});

$container->set('flash', function () {
    return new Slim\Flash\Messages();
});

$container->set(\PDO::class, function () {
    $conn = new \PDO('sqlite:database.sqlite');
    $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    return $conn;
});

$initFilePath = implode('/', [dirname(__DIR__), 'L27/init.sql']);
$initSql = file_get_contents($initFilePath);
$container->get(\PDO::class)->exec($initSql);

$app = AppFactory::createFromContainer($container);
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

/** @var \App\ProductsRepository */
$repo = $container->get(ProductsRepository::class);

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
});

$app->get('/products', function ($request, $response) use ($repo) {
    $flash = $this->get('flash')->getMessages();

    $params = [
        'flash' => $flash,
        'products' => $repo->getEntities()
    ];
    return $this->get('renderer')->render($response, 'products/index.phtml', $params);
})->setName('products');

$app->get('/products/new', function ($request, $response) {
    $params = [
        'product' => new Product(),
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'products/new.phtml', $params);
});

$app->get('/products/{id}', function ($request, $response, $args) use ($repo) {
    $id = (int) $args['id'];
    $product = $repo->find($id);

    if (!$product) {
        throw new Slim\Exception\HttpNotFoundException($request);
    }

    $params = [
        'product' => $product
    ];
    return $this->get('renderer')->render($response, 'products/show.phtml', $params);
})->setName('products');

$app->post('/products', function ($request, $response) use ($router, $repo) {
    $productData = $request->getParsedBodyParam('product');

    $validator = new App\L27\src\Validator();
    $errors = $validator->validate($productData);

    if (count($errors) === 0) {
        $product = Product::fromArray($productData);
        $repo->save($product);
        $this->get('flash')->addMessage('success', 'Product has been created');
        return $response->withRedirect($router->urlFor('products'));
    }

    $params = [
        'product' => Product::fromArray($productData),
        'errors' => $errors
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'products/new.phtml', $params);
});

$app->get('/products/{id}/edit', function ($request, $response, $args) use ($repo) {
    $id = $args['id'];

    $product = $repo->find($id);

    if (!$product) {
        throw new Slim\Exception\HttpNotFoundException($request);
    }

    $params = [
        'product' => $product,
        'errors' => []
    ];

    return $this->get('renderer')->render($response, 'products/edit.phtml', $params);
});

$app->put('/products/{id}', function ($request, $response, $args) use ($router, $repo) {
    $id = (int) $args['id'];

    $product = $repo->find($id);

    if (!$product) {
        throw new Slim\Exception\HttpNotFoundException($request);
    }

    $productData = $request->getParsedBodyParam('product');

    $validator = new App\L27\src\Validator();
    $errors = $validator->validate($productData);

    if (count($errors) === 0) {
        $product->setTitle($productData['title']);
        $product->setPrice($productData['price']);

        $repo->save($product);
        $this->get('flash')->addMessage('success', 'Product has been updated');
        return $response->withRedirect($router->urlFor('products'));
    }

    $params = [
        'product' => Product::fromArray($productData),
        'errors' => $errors
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'products/edit.phtml', $params);
});

$app->delete('/products/{id}', function ($request, $response, array $args) use ($router, $repo) {
    $id = $args['id'];

    $repo->delete($id);

    $this->get('flash')->addMessage('success', 'Product has been deleted');
    return $response->withRedirect($router->urlFor('products'));
});

$app->run();
