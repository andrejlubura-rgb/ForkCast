<?php

// GET /external/meals/search: searches TheMealDB by exactly one of q, category, area or ingredient.
Flight::route('GET /external/meals/search', function () {
    CurrentUser::getOrFail();

    // Only non-empty string values count as a filter; anything else is ignored.
    $filters = [];

    foreach (['q', 'category', 'area', 'ingredient'] as $name) {
        if (isset($_GET[$name]) && is_string($_GET[$name]) && trim($_GET[$name]) !== '') {
            $filters[$name] = trim($_GET[$name]);
        }
    }

    if (count($filters) !== 1) {
        throw ApiException::badRequest('Send exactly one of q, category, area or ingredient.');
    }

    $name = array_keys($filters)[0];
    $value = $filters[$name];
    $service = new MealDbService();

    // A name search returns full meals; the other filters return id, name and thumbnail only.
    if ($name === 'q') {
        Flight::json($service->search($value));
    } else {
        Flight::json($service->filter($name, $value));
    }
});

// GET /external/meals/random: returns one random meal from TheMealDB.
Flight::route('GET /external/meals/random', function () {
    CurrentUser::getOrFail();

    Flight::json((new MealDbService())->random());
});

// GET /external/meals/@id: returns one meal from TheMealDB by its id.
Flight::route('GET /external/meals/@id', function ($id) {
    CurrentUser::getOrFail();

    Flight::json((new MealDbService())->lookup((string) $id));
});
