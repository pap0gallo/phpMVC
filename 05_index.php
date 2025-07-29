<?php

$requestUri = $_SERVER['REQUEST_URI'];

$response = match ($requestUri) {
    '/' => '<a href="/welcome">welcome</a><br><a href="/not-found">not-found</a>',
    '/welcome' => '<a href="/">main</a>',
    default => null,
};

if ($response === null) {
    http_response_code(404);
    $response = 'Page not found. <a href="/">main</a>';
}

echo $response;