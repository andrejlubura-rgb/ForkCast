<?php

declare(strict_types=1);

// Command-line smoke test for the DAO layer.
// Every row it writes is created inside one transaction that is rolled back at the end,
// so the database is left unchanged.
// Run from the project root with: php backend/tests/dao_smoke_test.php
// The database must contain the sample data from backend/database/seed.sql.

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require __DIR__ . '/../bootstrap.php';

$passedChecks = 0;
$failedChecks = 0;

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
    $message = 'FAIL: ' . $label;

    if ($detail !== '') {
        $message .= ' -> ' . $detail;
    }

    echo $message . PHP_EOL;
}

// Passes only when the action throws a PDOException, as the database constraints require.
function checkThrowsPdoException(string $label, callable $action): void
{
    try {
        $action();
    } catch (PDOException $exception) {
        check($label, true);

        return;
    } catch (Throwable $exception) {
        check($label, false, 'threw ' . get_class($exception) . ' instead of PDOException');

        return;
    }

    check($label, false, 'no exception was thrown');
}

// Compares one field of a row with an expected value.
function fieldEquals(?array $row, string $field, $expected): bool
{
    return is_array($row)
        && array_key_exists($field, $row)
        && (string) $row[$field] === (string) $expected;
}

// Finds a row by id in the result of getAll().
function findRowById(array $rows, int $id): ?array
{
    foreach ($rows as $row) {
        if ((int) $row['id'] === $id) {
            return $row;
        }
    }

    return null;
}

// Finds a row by its name column, used for ingredients and shopping list entries.
function findRowByName(array $rows, string $name): ?array
{
    foreach ($rows as $row) {
        if (isset($row['name']) && $row['name'] === $name) {
            return $row;
        }
    }

    return null;
}

$connection = null;

try {
    $connection = Database::connect();
    $connection->beginTransaction();

    // --- UserDao -------------------------------------------------------------

    $userDao = new UserDao();
    $userEmail = 'smoke.test.user@forkcast.test';

    $userId = $userDao->insert([
        'first_name' => 'Smoke',
        'last_name' => 'Tester',
        'email' => $userEmail,
        'password_hash' => password_hash('SmokeTest123!', PASSWORD_DEFAULT),
        'role' => 'user',
    ]);
    check('UserDao::insert returns the new user id', $userId > 0, 'id: ' . $userId);

    $user = $userDao->getById($userId);
    check(
        'UserDao::getById returns the inserted user',
        $user !== null && $user['email'] === $userEmail
    );

    $userDao->update($userId, ['first_name' => 'Updated']);
    check(
        'UserDao::update changes first_name',
        fieldEquals($userDao->getById($userId), 'first_name', 'Updated')
    );

    check(
        'UserDao::getAll contains the inserted user',
        findRowById($userDao->getAll(1000), $userId) !== null
    );

    $userByEmail = $userDao->getByEmail($userEmail);
    check(
        'UserDao::getByEmail returns the inserted user',
        $userByEmail !== null && (int) $userByEmail['id'] === $userId
    );

    check('UserDao::delete removes the inserted user', $userDao->delete($userId));
    check('UserDao::getById returns null after delete', $userDao->getById($userId) === null);

    // --- CategoryDao ---------------------------------------------------------

    $categoryDao = new CategoryDao();
    $categoryName = 'Smoke Test Category';

    $categoryId = $categoryDao->insert([
        'name' => $categoryName,
        'description' => 'Created by the DAO smoke test.',
    ]);
    check('CategoryDao::insert returns the new category id', $categoryId > 0, 'id: ' . $categoryId);

    $category = $categoryDao->getById($categoryId);
    check(
        'CategoryDao::getById returns the inserted category',
        $category !== null && $category['name'] === $categoryName
    );

    $categoryDao->update($categoryId, ['description' => 'Updated by the DAO smoke test.']);
    check(
        'CategoryDao::update changes description',
        fieldEquals($categoryDao->getById($categoryId), 'description', 'Updated by the DAO smoke test.')
    );

    check(
        'CategoryDao::getAll contains the inserted category',
        findRowById($categoryDao->getAll(1000), $categoryId) !== null
    );

    $categoryByName = $categoryDao->getByName($categoryName);
    check(
        'CategoryDao::getByName returns the inserted category',
        $categoryByName !== null && (int) $categoryByName['id'] === $categoryId
    );

    check('CategoryDao::delete removes the inserted category', $categoryDao->delete($categoryId));
    check('CategoryDao::getById returns null after delete', $categoryDao->getById($categoryId) === null);

    // --- RecipeDao -----------------------------------------------------------

    $recipeDao = new RecipeDao();
    $recipeTitle = 'Smoke Test Recipe';

    $recipeId = $recipeDao->insert([
        'user_id' => 2,
        'category_id' => 1,
        'title' => $recipeTitle,
        'instructions' => 'Mix the ingredients and cook until done.',
        'prep_time_minutes' => 10,
        'servings' => 2,
        'source' => 'own',
    ]);
    check('RecipeDao::insert returns the new recipe id', $recipeId > 0, 'id: ' . $recipeId);

    $recipe = $recipeDao->getById($recipeId);
    check(
        'RecipeDao::getById returns the inserted recipe',
        $recipe !== null && $recipe['title'] === $recipeTitle
    );

    $recipeDao->update($recipeId, ['title' => 'Smoke Test Recipe Updated']);
    check(
        'RecipeDao::update changes title',
        fieldEquals($recipeDao->getById($recipeId), 'title', 'Smoke Test Recipe Updated')
    );

    check(
        'RecipeDao::getAll contains the inserted recipe',
        findRowById($recipeDao->getAll(1000), $recipeId) !== null
    );

    $textResults = $recipeDao->search('Smoke Test Recipe Updated', null, null);
    check(
        'RecipeDao::search with text only finds the recipe',
        findRowById($textResults, $recipeId) !== null
    );

    $categoryResults = $recipeDao->search(null, 1, null);
    check(
        'RecipeDao::search with category only finds the recipe',
        findRowById($categoryResults, $recipeId) !== null
    );

    $bothResults = $recipeDao->search('Smoke', 1, null);
    check(
        'RecipeDao::search with text and category finds the recipe',
        findRowById($bothResults, $recipeId) !== null
    );

    check('RecipeDao::delete removes the inserted recipe', $recipeDao->delete($recipeId));
    check('RecipeDao::getById returns null after delete', $recipeDao->getById($recipeId) === null);

    // --- RecipeIngredientDao -------------------------------------------------

    $ingredientDao = new RecipeIngredientDao();
    $ingredientName = 'Smoke Test Ingredient';

    $ingredientId = $ingredientDao->insert([
        'recipe_id' => 1,
        'name' => $ingredientName,
        'measure' => '1 tbsp',
    ]);
    check(
        'RecipeIngredientDao::insert returns the new ingredient id',
        $ingredientId > 0,
        'id: ' . $ingredientId
    );

    $ingredient = $ingredientDao->getById($ingredientId);
    check(
        'RecipeIngredientDao::getById returns the inserted ingredient',
        $ingredient !== null && $ingredient['name'] === $ingredientName
    );

    $ingredientDao->update($ingredientId, ['measure' => '2 tbsp']);
    check(
        'RecipeIngredientDao::update changes measure',
        fieldEquals($ingredientDao->getById($ingredientId), 'measure', '2 tbsp')
    );

    check(
        'RecipeIngredientDao::getAll contains the inserted ingredient',
        findRowById($ingredientDao->getAll(1000), $ingredientId) !== null
    );

    check(
        'RecipeIngredientDao::getByRecipeId returns the inserted ingredient',
        findRowById($ingredientDao->getByRecipeId(1), $ingredientId) !== null
    );

    check(
        'RecipeIngredientDao::delete removes the inserted ingredient',
        $ingredientDao->delete($ingredientId)
    );
    check(
        'RecipeIngredientDao::getById returns null after delete',
        $ingredientDao->getById($ingredientId) === null
    );

    // --- MealPlanDao ---------------------------------------------------------

    $mealPlanDao = new MealPlanDao();
    $planWeekStart = '2030-01-06';

    $planId = $mealPlanDao->insert([
        'user_id' => 2,
        'name' => 'Smoke Test Plan',
        'week_start_date' => $planWeekStart,
    ]);
    check('MealPlanDao::insert returns the new plan id', $planId > 0, 'id: ' . $planId);

    $plan = $mealPlanDao->getById($planId);
    check(
        'MealPlanDao::getById returns the inserted plan',
        $plan !== null && $plan['name'] === 'Smoke Test Plan'
    );

    $mealPlanDao->update($planId, ['name' => 'Smoke Test Plan Updated']);
    check(
        'MealPlanDao::update changes name',
        fieldEquals($mealPlanDao->getById($planId), 'name', 'Smoke Test Plan Updated')
    );

    check(
        'MealPlanDao::getAll contains the inserted plan',
        findRowById($mealPlanDao->getAll(1000), $planId) !== null
    );

    $planByWeek = $mealPlanDao->getByUserAndWeek(2, $planWeekStart);
    check(
        'MealPlanDao::getByUserAndWeek returns the inserted plan',
        $planByWeek !== null && (int) $planByWeek['id'] === $planId
    );

    check('MealPlanDao::delete removes the inserted plan', $mealPlanDao->delete($planId));
    check('MealPlanDao::getById returns null after delete', $mealPlanDao->getById($planId) === null);

    // --- PlannedMealDao ------------------------------------------------------

    $plannedMealDao = new PlannedMealDao();

    $shoppingList = $plannedMealDao->getShoppingList(1);
    $spaghetti = findRowByName($shoppingList, 'Spaghetti');
    check(
        'PlannedMealDao::getShoppingList returns grouped ingredients for plan 1',
        $spaghetti !== null
            && array_key_exists('measures', $spaghetti)
            && array_key_exists('times_used', $spaghetti),
        'rows: ' . count($shoppingList)
    );

    $plannedMealId = $plannedMealDao->insert([
        'plan_id' => 1,
        'recipe_id' => 1,
        'day_of_week' => 7,
        'meal_type' => 'snack',
        'servings' => 2,
    ]);
    check(
        'PlannedMealDao::insert returns the new planned meal id',
        $plannedMealId > 0,
        'id: ' . $plannedMealId
    );

    $plannedMeal = $plannedMealDao->getById($plannedMealId);
    check(
        'PlannedMealDao::getById returns the inserted planned meal',
        $plannedMeal !== null && (int) $plannedMeal['plan_id'] === 1
    );

    $plannedMealDao->update($plannedMealId, ['servings' => 3]);
    check(
        'PlannedMealDao::update changes servings',
        fieldEquals($plannedMealDao->getById($plannedMealId), 'servings', 3)
    );

    check(
        'PlannedMealDao::getAll contains the inserted planned meal',
        findRowById($plannedMealDao->getAll(1000), $plannedMealId) !== null
    );

    $plannedMealBySlot = $plannedMealDao->getBySlot(1, 7, 'snack');
    check(
        'PlannedMealDao::getBySlot returns the inserted planned meal',
        $plannedMealBySlot !== null && (int) $plannedMealBySlot['id'] === $plannedMealId
    );

    check(
        'PlannedMealDao::delete removes the inserted planned meal',
        $plannedMealDao->delete($plannedMealId)
    );
    check(
        'PlannedMealDao::getById returns null after delete',
        $plannedMealDao->getById($plannedMealId) === null
    );

    // --- Database constraint checks ------------------------------------------

    checkThrowsPdoException(
        'UserDao::insert fails with an existing email',
        function () use ($userDao): void {
            $userDao->insert([
                'first_name' => 'Duplicate',
                'last_name' => 'Email',
                'email' => 'user1@forkcast.test',
                'password_hash' => password_hash('SmokeTest123!', PASSWORD_DEFAULT),
                'role' => 'user',
            ]);
        }
    );

    checkThrowsPdoException(
        'CategoryDao::delete fails when category 8 still has recipes',
        function () use ($categoryDao): void {
            $categoryDao->delete(8);
        }
    );

    checkThrowsPdoException(
        'PlannedMealDao::insert fails on an occupied slot',
        function () use ($plannedMealDao): void {
            $plannedMealDao->insert([
                'plan_id' => 1,
                'recipe_id' => 1,
                'day_of_week' => 1,
                'meal_type' => 'dinner',
                'servings' => 2,
            ]);
        }
    );
} catch (Throwable $exception) {
    check(
        'Smoke test ran without an unexpected error',
        false,
        get_class($exception) . ': ' . $exception->getMessage()
    );
} finally {
    if ($connection instanceof PDO && $connection->inTransaction()) {
        $connection->rollBack();
    }
}

$totalChecks = $passedChecks + $failedChecks;

echo PHP_EOL;
echo 'SUMMARY: ' . $passedChecks . ' passed, ' . $failedChecks . ' failed, '
    . $totalChecks . ' checks.' . PHP_EOL;
echo 'RESULT: ' . ($failedChecks > 0 ? 'FAILED' : 'OK')
    . ' (all database changes rolled back)' . PHP_EOL;

exit($failedChecks > 0 ? 1 : 0);
