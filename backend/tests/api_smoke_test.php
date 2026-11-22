<?php

declare(strict_types=1);

// Command-line smoke test for the HTTP API.
// Run from the project root with: php backend/tests/api_smoke_test.php [baseUrl]
// Default base URL: http://localhost/forkcast/ForkCast/backend
// The script relies on the sample data (user 1 = admin, 2 = user, 3 = other user)
// and deletes every row it creates before it exits.

if (PHP_SAPI !== 'cli') {
    exit(1);
}

if (!function_exists('curl_init')) {
    fwrite(STDERR, 'cURL is required to run this smoke test.' . PHP_EOL);
    exit(1);
}

$baseUrl = rtrim($argv[1] ?? 'http://localhost/forkcast/ForkCast/backend', '/');

$passedChecks = 0;
$failedChecks = 0;
$skippedChecks = 0;

// Prints one line per check and keeps the counters for the final summary.
function check(string $label, bool $condition, string $detail = ''): void
{
    global $passedChecks, $failedChecks;

    if ($condition) {
        $passedChecks++;
        echo 'PASS: ' . $label . PHP_EOL;

        return;
    }

    $failedChecks++;
    $line = 'FAIL: ' . $label;

    if ($detail !== '') {
        $line .= ' -> ' . $detail;
    }

    echo $line . PHP_EOL;
}

// Compares the HTTP status of one response with the expected status.
function checkStatus(string $label, array $response, int $expected): void
{
    $detail = 'expected ' . $expected . ', got ' . $response['status'];

    if ($response['error'] !== '') {
        $detail .= ' (' . $response['error'] . ')';
    }

    check($label, $response['status'] === $expected, $detail);
}

// Reports a check that could not run because an external service is unavailable.
function skip(string $label, string $reason): void
{
    global $skippedChecks;

    $skippedChecks++;
    echo 'SKIP: ' . $label . ' -> ' . $reason . PHP_EOL;
}

// Sends one API request and returns its status, decoded JSON data and any cURL error.
function api(string $baseUrl, string $method, string $path, ?int $userId = null, ?array $body = null): array
{
    $handle = curl_init();
    $headers = ['Accept: application/json'];

    if ($userId !== null) {
        $headers[] = 'X-User-Id: ' . $userId;
    }

    if ($body !== null) {
        $json = json_encode($body);
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Content-Length: ' . strlen($json);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $json);
    }

    curl_setopt($handle, CURLOPT_URL, $baseUrl . '/' . ltrim($path, '/'));
    curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($handle, CURLOPT_TIMEOUT, 20);

    $raw = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $error = curl_error($handle);
    curl_close($handle);

    $data = null;

    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        $data = is_array($decoded) ? $decoded : null;
    }

    return ['status' => $status, 'data' => $data, 'error' => $error];
}

// Returns the id of a created object, or null when the response has no usable id.
function createdId(array $response): ?int
{
    if (isset($response['data']['id']) && is_numeric($response['data']['id'])) {
        return (int) $response['data']['id'];
    }

    return null;
}

// Ids of the rows this script creates; cleared as soon as a row is deleted by a check.
$categoryId = null;
$userId = null;
$recipeId = null;
$planId = null;

try {
    // --- Public info and identification -------------------------------------

    checkStatus('GET / returns 200', api($baseUrl, 'GET', '/'), 200);
    checkStatus('GET /categories without X-User-Id returns 401', api($baseUrl, 'GET', '/categories'), 401);
    checkStatus('GET /categories as user 2 returns 200', api($baseUrl, 'GET', '/categories', 2), 200);

    // --- Categories ---------------------------------------------------------

    checkStatus(
        'POST /categories as user 2 returns 403',
        api($baseUrl, 'POST', '/categories', 2, ['name' => 'Not Allowed']),
        403
    );

    checkStatus(
        'POST /categories as admin with a too-short name returns 400',
        api($baseUrl, 'POST', '/categories', 1, ['name' => 'A']),
        400
    );

    $categoryName = 'API Smoke ' . substr(md5(uniqid('', true)), 0, 10);
    $categoryResponse = api($baseUrl, 'POST', '/categories', 1, [
        'name' => $categoryName,
        'description' => 'Temporary category created by the API smoke test.',
    ]);
    checkStatus('POST /categories as admin with a valid unique name returns 201', $categoryResponse, 201);
    $categoryId = createdId($categoryResponse);

    checkStatus(
        'POST /categories with the same name returns 400',
        api($baseUrl, 'POST', '/categories', 1, ['name' => $categoryName]),
        400
    );

    checkStatus(
        'PUT /categories/{id} as admin returns 200',
        api($baseUrl, 'PUT', '/categories/' . $categoryId, 1, [
            'name' => $categoryName . ' Updated',
            'description' => 'Updated by the API smoke test.',
        ]),
        200
    );

    checkStatus(
        'GET /categories/999999999 returns 404',
        api($baseUrl, 'GET', '/categories/999999999', 1),
        404
    );

    checkStatus(
        'DELETE /categories/{id} as admin returns 200',
        api($baseUrl, 'DELETE', '/categories/' . $categoryId, 1),
        200
    );
    $categoryId = null;

    // --- Users --------------------------------------------------------------

    $email = 'api.smoke.' . substr(md5(uniqid('', true)), 0, 12) . '@forkcast.test';
    $userBody = [
        'first_name' => 'Api',
        'last_name' => 'Smoke',
        'email' => $email,
        'password' => 'SmokeTest123!',
    ];

    $userResponse = api($baseUrl, 'POST', '/users', null, $userBody);
    checkStatus('POST /users with a random email returns 201', $userResponse, 201);
    $userId = createdId($userResponse);

    checkStatus(
        'POST /users with the same email returns 400',
        api($baseUrl, 'POST', '/users', null, $userBody),
        400
    );

    checkStatus('GET /users as user 2 returns 403', api($baseUrl, 'GET', '/users', 2), 403);

    // --- Recipes ------------------------------------------------------------

    $recipeBody = [
        'category_id' => 1,
        'title' => 'API Smoke Recipe ' . substr(md5(uniqid('', true)), 0, 8),
        'instructions' => 'Mix all the ingredients together and cook until done.',
        'prep_time_minutes' => 10,
        'servings' => 2,
        'ingredients' => [
            ['name' => 'Smoke test ingredient', 'measure' => '1 cup'],
        ],
    ];

    $recipeResponse = api($baseUrl, 'POST', '/recipes', 2, $recipeBody);
    checkStatus('POST /recipes with valid data returns 201', $recipeResponse, 201);
    $recipeId = createdId($recipeResponse);

    $emptyIngredientsBody = $recipeBody;
    $emptyIngredientsBody['ingredients'] = [];
    checkStatus(
        'POST /recipes with an empty ingredients array returns 400',
        api($baseUrl, 'POST', '/recipes', 2, $emptyIngredientsBody),
        400
    );

    checkStatus(
        'PUT /recipes/{id} as user 3 returns 403',
        api($baseUrl, 'PUT', '/recipes/' . $recipeId, 3, ['title' => 'Not the owner']),
        403
    );

    checkStatus(
        'DELETE /recipes/{id} as the owner returns 200',
        api($baseUrl, 'DELETE', '/recipes/' . $recipeId, 2),
        200
    );
    $recipeId = null;

    // --- Plans and planned meals --------------------------------------------

    // A Monday a few years in the future is guaranteed to be free in the sample data.
    $monday = date('Y-m-d', strtotime('next monday', strtotime('+' . random_int(2, 10) . ' years')));
    $notMonday = date('Y-m-d', strtotime($monday . ' +1 day'));

    checkStatus(
        'POST /plans with a non-Monday date returns 400',
        api($baseUrl, 'POST', '/plans', 2, [
            'name' => 'API Smoke Plan',
            'week_start_date' => $notMonday,
        ]),
        400
    );

    $planResponse = api($baseUrl, 'POST', '/plans', 2, [
        'name' => 'API Smoke Plan',
        'week_start_date' => $monday,
    ]);
    checkStatus('POST /plans with a free Monday returns 201', $planResponse, 201);
    $planId = createdId($planResponse);

    // Recipe 1 comes from the sample data, so it is independent of the recipe deleted above.
    $mealBody = [
        'recipe_id' => 1,
        'day_of_week' => 1,
        'meal_type' => 'dinner',
        'servings' => 2,
    ];

    checkStatus(
        'POST /plans/{id}/meals into a free slot returns 201',
        api($baseUrl, 'POST', '/plans/' . $planId . '/meals', 2, $mealBody),
        201
    );

    checkStatus(
        'POST /plans/{id}/meals into the same slot returns 400',
        api($baseUrl, 'POST', '/plans/' . $planId . '/meals', 2, $mealBody),
        400
    );

    checkStatus(
        'GET /plans/{id} as user 3 returns 403',
        api($baseUrl, 'GET', '/plans/' . $planId, 3),
        403
    );

    // --- External API --------------------------------------------------------

    $searchResponse = api($baseUrl, 'GET', '/external/meals/search?q=chicken', 2);

    if ($searchResponse['status'] === 503) {
        skip('GET /external/meals/search?q=chicken returns 200', 'TheMealDB is unavailable');
    } else {
        checkStatus('GET /external/meals/search?q=chicken returns 200', $searchResponse, 200);
    }

    checkStatus(
        'GET /external/meals/search without parameters returns 400',
        api($baseUrl, 'GET', '/external/meals/search', 2),
        400
    );

    // --- Unknown route -------------------------------------------------------

    checkStatus('GET /nothing returns 404', api($baseUrl, 'GET', '/nothing'), 404);
} catch (Throwable $exception) {
    check(
        'Smoke test ran without an unexpected error',
        false,
        get_class($exception) . ': ' . $exception->getMessage()
    );
} finally {
    // Delete the plan first because removing it also removes its planned meals.
    if ($planId !== null) {
        api($baseUrl, 'DELETE', '/plans/' . $planId, 2);
    }

    if ($recipeId !== null) {
        api($baseUrl, 'DELETE', '/recipes/' . $recipeId, 2);
    }

    if ($categoryId !== null) {
        api($baseUrl, 'DELETE', '/categories/' . $categoryId, 1);
    }

    if ($userId !== null) {
        api($baseUrl, 'DELETE', '/users/' . $userId, 1);
    }
}

$totalChecks = $passedChecks + $failedChecks + $skippedChecks;

echo PHP_EOL;
echo 'SUMMARY: ' . $passedChecks . ' passed, ' . $failedChecks . ' failed, '
    . $skippedChecks . ' skipped, ' . $totalChecks . ' checks.' . PHP_EOL;
echo 'RESULT: ' . ($failedChecks > 0 ? 'FAILED' : 'OK') . PHP_EOL;

exit($failedChecks > 0 ? 1 : 0);
