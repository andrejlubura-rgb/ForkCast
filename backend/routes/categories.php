<?php

// GET /categories: returns every category for an identified user.
Flight::route('GET /categories', function () {
    $actor = CurrentUser::getOrFail();
    $service = new CategoryService();

    Flight::json($service->getAll($actor));
});

// GET /categories/@id: returns one category for an identified user.
Flight::route('GET /categories/@id:[0-9]+', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new CategoryService();

    Flight::json($service->getById($actor, (int) $id));
});

// POST /categories: creates a category; admins only.
Flight::route('POST /categories', function () {
    $actor = CurrentUser::getOrFail();
    $service = new CategoryService();

    Flight::json($service->create($actor, jsonBody()), 201);
});

// PUT /categories/@id: updates a category; admins only.
Flight::route('PUT /categories/@id:[0-9]+', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new CategoryService();

    Flight::json($service->update($actor, (int) $id, jsonBody()));
});

// DELETE /categories/@id: deletes a category; admins only.
Flight::route('DELETE /categories/@id:[0-9]+', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new CategoryService();

    $service->delete($actor, (int) $id);

    Flight::json(['message' => 'Deleted']);
});
