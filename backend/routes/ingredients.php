<?php

// GET /recipes/@id/ingredients: returns every ingredient of a recipe for an identified user.
Flight::route('GET /recipes/@id:[0-9]+/ingredients', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new RecipeIngredientService();

    Flight::json($service->getByRecipe($actor, (int) $id));
});

// POST /recipes/@id/ingredients: adds an ingredient; the owner of the recipe or an admin.
Flight::route('POST /recipes/@id:[0-9]+/ingredients', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new RecipeIngredientService();

    Flight::json($service->create($actor, (int) $id, jsonBody()), 201);
});

// PUT /ingredients/@id: updates an ingredient; the owner of its recipe or an admin.
Flight::route('PUT /ingredients/@id:[0-9]+', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new RecipeIngredientService();

    Flight::json($service->update($actor, (int) $id, jsonBody()));
});

// DELETE /ingredients/@id: deletes an ingredient; the owner of its recipe or an admin.
Flight::route('DELETE /ingredients/@id:[0-9]+', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new RecipeIngredientService();

    $service->delete($actor, (int) $id);

    Flight::json(['message' => 'Deleted']);
});
