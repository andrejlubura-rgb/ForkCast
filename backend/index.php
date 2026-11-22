<?php

// Entry point of the ForkCast API: loads the framework, the autoloader, the routes and the error handling.

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap.php';

ini_set('display_errors', '0');

require __DIR__ . '/routes/helpers.php';

// Loads every other route file in alphabetical order, so the route order stays predictable.
$routeFiles = glob(__DIR__ . '/routes/*.php') ?: [];
sort($routeFiles);

foreach ($routeFiles as $routeFile) {
    if (basename($routeFile) === 'helpers.php') {
        continue;
    }

    require $routeFile;
}

// Turns any thrown exception into a JSON error response without leaking details.
Flight::map('error', function (Throwable $exception) {
    if ($exception instanceof ApiException) {
        $payload = ['error' => $exception->getMessage()];
        $errors = $exception->getErrors();

        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        Flight::json($payload, $exception->getCode());
        Flight::response()->send();

        return;
    }

    Logger::error(
        get_class($exception) . ': ' . $exception->getMessage()
        . ' in ' . $exception->getFile() . ':' . $exception->getLine()
    );

    Flight::json(['error' => 'Internal server error'], 500);
    Flight::response()->send();
});

// Public info route.
Flight::route('GET /', function () {
    Flight::json(['name' => 'ForkCast API', 'version' => '1.0.0']);
});

// Any unmatched method and path ends as a JSON 404.
Flight::route('*', function () {
    throw ApiException::notFound('Route not found');
});

Flight::start();
