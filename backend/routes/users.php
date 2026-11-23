<?php

// GET /users: returns one page of users; admins only.
Flight::route('GET /users', function () {
    $actor = CurrentUser::getOrFail();
    $service = new UserService();

    // Paging defaults come from docs/API.md; the service keeps limit at 1-100 and offset at 0 or more.
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;

    Flight::json($service->getAll($actor, $limit, $offset));
});

// GET /users/@id: returns one user for that same user or an admin.
Flight::route('GET /users/@id:[0-9]+', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new UserService();

    Flight::json($service->getById($actor, (int) $id));
});

// POST /users: public registration; the created user always gets the role "user".
Flight::route('POST /users', function () {
    $service = new UserService();

    Flight::json($service->create(jsonBody()), 201);
});

// PUT /users/@id: updates a user; that same user or an admin.
Flight::route('PUT /users/@id:[0-9]+', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new UserService();

    Flight::json($service->update($actor, (int) $id, jsonBody()));
});

// DELETE /users/@id: deletes a user; admins only.
Flight::route('DELETE /users/@id:[0-9]+', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new UserService();

    $service->delete($actor, (int) $id);

    Flight::json(['message' => 'Deleted']);
});
