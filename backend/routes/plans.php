<?php

// GET /plans: returns one page of plans; a user sees only their own plans, an admin sees every plan.
Flight::route('GET /plans', function () {
    $actor = CurrentUser::getOrFail();
    $service = new MealPlanService();

    // Paging defaults come from docs/API.md; the service keeps limit at 1-100 and offset at 0 or more.
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;

    Flight::json($service->getAll($actor, $limit, $offset));
});

// GET /plans/@id/shopping-list: returns the grouped ingredients of a plan; the owner of the plan or an admin.
Flight::route('GET /plans/@id:[0-9]+/shopping-list', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new MealPlanService();

    Flight::json($service->getShoppingList($actor, (int) $id));
});

// GET /plans/@id: returns one plan with its planned meals; the owner of the plan or an admin.
Flight::route('GET /plans/@id:[0-9]+', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new MealPlanService();

    Flight::json($service->getById($actor, (int) $id));
});

// POST /plans: creates a plan for the identified user.
Flight::route('POST /plans', function () {
    $actor = CurrentUser::getOrFail();
    $service = new MealPlanService();

    Flight::json($service->create($actor, jsonBody()), 201);
});

// PUT /plans/@id: updates a plan; the owner of the plan or an admin.
Flight::route('PUT /plans/@id:[0-9]+', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new MealPlanService();

    Flight::json($service->update($actor, (int) $id, jsonBody()));
});

// DELETE /plans/@id: deletes a plan; the owner of the plan or an admin.
Flight::route('DELETE /plans/@id:[0-9]+', function ($id) {
    $actor = CurrentUser::getOrFail();
    $service = new MealPlanService();

    $service->delete($actor, (int) $id);

    Flight::json(['message' => 'Deleted']);
});
