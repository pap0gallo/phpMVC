<?php
// Получаем все текущие переменные окружения
$envs = getenv(); // или $_ENV

// Формируем содержимое .env
$envContent = '';
foreach ($envs as $key => $value) {
    // Экранируем значения с пробелами или спецсимволами
    if (preg_match('/\s/', $value)) {
        $value = '"' . addslashes($value) . '"';
    }
    $envContent .= "$key=$value\n";
}

// Записываем в .env
file_put_contents(__DIR__ . '/.env', $envContent);

echo ".env файл сгенерирован!";