<?php

// GET /plans/@id/meals: returns every planned meal of a plan; the owner of the plan or an admin.
Flight::route('GET /plans/@id:[0-9]+/meals', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new PlannedMealService();

    Flight::json($service->getByPlan($actor, (int) $id));
});

// POST /plans/@id/meals: adds a meal to a plan; the owner of the plan or an admin.
Flight::route('POST /plans/@id:[0-9]+/meals', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new PlannedMealService();

    Flight::json($service->create($actor, (int) $id, jsonBody()), 201);
});

// PUT /planned-meals/@id: updates a planned meal; the owner of its plan or an admin.
Flight::route('PUT /planned-meals/@id:[0-9]+', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new PlannedMealService();

    Flight::json($service->update($actor, (int) $id, jsonBody()));
});

// DELETE /planned-meals/@id: deletes a planned meal; the owner of its plan or an admin.
Flight::route('DELETE /planned-meals/@id:[0-9]+', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new PlannedMealService();

    $service->delete($actor, (int) $id);

    Flight::json(['message' => 'Deleted']);
});
