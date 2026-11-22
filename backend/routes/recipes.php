<?php

// GET /recipes: returns one page of recipes for an identified user, filtered by search, category_id and mine.
Flight::route('GET /recipes', function () {
    $actor = CurrentUser::getOrFail();
    $service = new RecipeService();

    // The service reads the query values it knows and clamps limit and offset.
    Flight::json($service->getAll($actor, $_GET));
});

// GET /recipes/@id: returns one recipe with its ingredients for an identified user.
Flight::route('GET /recipes/@id:[0-9]+', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new RecipeService();

    Flight::json($service->getById($actor, (int) $id));
});

// POST /recipes: creates a recipe with its ingredients for the identified user.
Flight::route('POST /recipes', function () {
    $actor = CurrentUser::getOrFail();
    $service = new RecipeService();

    Flight::json($service->create($actor, jsonBody()), 201);
});

// POST /recipes/import/@externalId: imports a TheMealDB meal; 201 when it is new, 200 when it already existed.
Flight::route('POST /recipes/import/@externalId', function ($externalId) {
    $actor = CurrentUser::getOrFail();
    $service = new RecipeService();

    list($recipe, $created) = $service->importFromMealDb($actor, (string) $externalId);

    Flight::json($recipe, $created ? 201 : 200);
});

// PUT /recipes/@id: updates a recipe; the owner of the recipe or an admin.
Flight::route('PUT /recipes/@id:[0-9]+', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new RecipeService();

    Flight::json($service->update($actor, (int) $id, jsonBody()));
});

// DELETE /recipes/@id: deletes a recipe; the owner of the recipe or an admin.
Flight::route('DELETE /recipes/@id:[0-9]+', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new RecipeService();

    $service->delete($actor, (int) $id);

    Flight::json(['message' => 'Deleted']);
});
