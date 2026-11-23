<?php

// Business rules and validation for recipes; the only layer that calls RecipeDao.
class RecipeService
{
    // DAO used for every recipe query.
    private RecipeDao $dao;

    // DAO used for the ingredient lines that belong to a recipe.
    private RecipeIngredientDao $ingredientDao;

    // DAO used to check that a category exists.
    private CategoryDao $categoryDao;

    // DAO used to check whether a recipe is used in a plan.
    private PlannedMealDao $plannedMealDao;

    // Creates the DAOs used by this service.
    public function __construct()
    {
        $this->dao = new RecipeDao();
        $this->ingredientDao = new RecipeIngredientDao();
        $this->categoryDao = new CategoryDao();
        $this->plannedMealDao = new PlannedMealDao();
    }

    // Returns one page of recipes for an identified user, filtered by search, category and owner.
    public function getAll(array $actor, array $filters): array
    {
        // Only the query values that were sent are used; unknown or invalid values are ignored.
        $text = null;

        if (isset($filters['search']) && is_string($filters['search']) && trim($filters['search']) !== '') {
            $text = trim($filters['search']);
        }

        $categoryId = null;

        if (isset($filters['category_id']) && is_string($filters['category_id'])
            && ctype_digit($filters['category_id']) && (int) $filters['category_id'] > 0) {
            $categoryId = (int) $filters['category_id'];
        }

        // mine=true limits the list to the recipes of the caller.
        $mine = isset($filters['mine']) && filter_var($filters['mine'], FILTER_VALIDATE_BOOLEAN);
        $userId = $mine && isset($actor['id']) ? (int) $actor['id'] : null;

        // Keeps paging inside the range from docs/API.md: limit 1-100 (default 20) and offset 0 or more.
        $limit = isset($filters['limit']) ? (int) $filters['limit'] : 20;
        $offset = isset($filters['offset']) ? (int) $filters['offset'] : 0;

        if ($limit < 1) {
            $limit = 20;
        }

        if ($limit > 100) {
            $limit = 100;
        }

        if ($offset < 0) {
            $offset = 0;
        }

        return $this->dao->search($text, $categoryId, $userId, $limit, $offset);
    }

    // Returns one recipe with its ingredients for an identified user, or throws 404.
    public function getById(array $actor, int $id): array
    {
        $recipe = $this->dao->getByIdWithDetails($id);

        if ($recipe === null) {
            throw ApiException::notFound('Recipe not found');
        }

        $recipe['ingredients'] = $this->ingredientDao->getByRecipeId($id);

        return $recipe;
    }

    // Creates a recipe with its ingredients in one transaction; the caller always owns it and it is always "own".
    public function create(array $actor, array $data): array
    {
        $input = $this->validate($data, true);
        $ingredients = $input['ingredients'];
        unset($input['ingredients']);

        $this->requireCategory((int) $input['category_id']);

        // source and user_id are set by the backend and can never come from the client.
        $input['source'] = 'own';
        $input['user_id'] = (int) $actor['id'];

        $this->dao->beginTransaction();

        try {
            $id = $this->dao->insert($input);
            $this->insertIngredients($id, $ingredients);
            $this->dao->commit();
        } catch (Throwable $exception) {
            $this->rollBackQuietly();
            throw $exception;
        }

        return $this->getById($actor, $id);
    }

    // Imports a TheMealDB meal for the caller; returns [recipe, created] and reuses the recipe when it was imported before.
    public function importFromMealDb(array $actor, string $externalId): array
    {
        $externalId = trim($externalId);

        // external_id is a varchar(20) in the database, so longer values could never be stored.
        if ($externalId === '' || mb_strlen($externalId, 'UTF-8') > 20) {
            throw ApiException::badRequest('Invalid TheMealDB meal id.');
        }

        $userId = (int) $actor['id'];

        // Importing the same meal twice returns the recipe that already exists.
        $existing = $this->dao->getByExternalId($userId, $externalId);

        if ($existing !== null) {
            return [$this->getById($actor, (int) $existing['id']), false];
        }

        // The lookup happens before the transaction, so a slow external API never holds a database lock.
        $meal = (new MealDbService())->lookup($externalId);

        $title = $meal['name'] ?? '';
        $instructions = $meal['instructions'] ?? '';
        $categoryName = $meal['category'] ?? '';

        if ($title === '' || $instructions === '' || $categoryName === '') {
            throw ApiException::badRequest('This meal cannot be imported because some of its data is missing.');
        }

        $recipe = [
            'user_id' => $userId,
            'title' => $title,
            'instructions' => $instructions,
            // TheMealDB has no preparation time and no serving count.
            'prep_time_minutes' => 30,
            'servings' => 4,
            'image_url' => $meal['thumbnail'] ?? null,
            'source' => 'mealdb',
            'external_id' => $externalId,
        ];

        $ingredients = [];

        foreach ($meal['ingredients'] ?? [] as $line) {
            $ingredient = ['name' => $line['name']];

            if (isset($line['measure']) && $line['measure'] !== '') {
                $ingredient['measure'] = $line['measure'];
            }

            $ingredients[] = $ingredient;
        }

        $this->dao->beginTransaction();

        try {
            // getByName compares case-insensitively because categories.name uses a case-insensitive collation.
            $category = $this->categoryDao->getByName($categoryName);
            $recipe['category_id'] = $category !== null
                ? (int) $category['id']
                : $this->categoryDao->insert(['name' => $categoryName]);

            $id = $this->dao->insert($recipe);
            $this->insertIngredients($id, $ingredients);
            $this->dao->commit();
        } catch (Throwable $exception) {
            $this->rollBackQuietly();
            throw $exception;
        }

        return [$this->getById($actor, $id), true];
    }

    // Updates a recipe for its owner or an admin; ingredients replace all existing ones when they are sent.
    public function update(array $actor, int $id, array $data): array
    {
        $existing = $this->dao->getById($id);

        if ($existing === null) {
            throw ApiException::notFound('Recipe not found');
        }

        Access::requireOwnerOrAdmin($actor, (int) $existing['user_id']);

        $input = $this->validate($data, false);

        $this->requireCategory((int) $input['category_id']);

        // source, external_id and user_id are not part of the validated input, so they can never change.
        $replaceIngredients = array_key_exists('ingredients', $input);
        $ingredients = $replaceIngredients ? $input['ingredients'] : [];
        unset($input['ingredients']);

        $this->dao->beginTransaction();

        try {
            $this->dao->update($id, $input);

            if ($replaceIngredients) {
                $this->ingredientDao->deleteByRecipeId($id);
                $this->insertIngredients($id, $ingredients);
            }

            $this->dao->commit();
        } catch (Throwable $exception) {
            $this->rollBackQuietly();
            throw $exception;
        }

        return $this->getById($actor, $id);
    }

    // Deletes a recipe for its owner or an admin; a recipe used in any plan cannot be deleted.
    public function delete(array $actor, int $id): void
    {
        $existing = $this->dao->getById($id);

        if ($existing === null) {
            throw ApiException::notFound('Recipe not found');
        }

        Access::requireOwnerOrAdmin($actor, (int) $existing['user_id']);

        if ($this->plannedMealDao->countByRecipeId($id) > 0) {
            throw ApiException::badRequest('This recipe is used in a plan and cannot be deleted.');
        }

        $this->dao->delete($id);
    }

    // Throws a 400 when the category of the recipe does not exist.
    private function requireCategory(int $categoryId): void
    {
        if ($this->categoryDao->getById($categoryId) === null) {
            throw ApiException::validation(['category_id' => 'Category not found.']);
        }
    }

    // Inserts the validated ingredient lines of one recipe; the caller owns the transaction.
    private function insertIngredients(int $recipeId, array $ingredients): void
    {
        foreach ($ingredients as $ingredient) {
            $line = ['recipe_id' => $recipeId, 'name' => $ingredient['name']];

            if (array_key_exists('measure', $ingredient)) {
                $line['measure'] = $ingredient['measure'];
            }

            $this->ingredientDao->insert($line);
        }
    }

    // Rolls back the active transaction without hiding the exception that caused it.
    private function rollBackQuietly(): void
    {
        try {
            $this->dao->rollBack();
        } catch (Throwable $ignored) {
            // A failed commit may already have closed the transaction; the original error is more useful.
        }
    }

    // Validates the recipe fields and the optional or required ingredients array.
    private function validate(array $data, bool $ingredientsRequired): array
    {
        $validator = new Validator($data);
        $validator->int('category_id', 1, 2147483647);
        $validator->string('title', 3, 100);
        $validator->string('instructions', 10, 20000);
        $validator->int('prep_time_minutes', 1, 600);
        $validator->int('servings', 1, 20);
        $validator->url('image_url', 500, false);

        // Ingredients are validated by hand, because Validator only handles flat fields.
        $ingredientErrors = [];
        $ingredients = null;

        if (array_key_exists('ingredients', $data) && $data['ingredients'] !== null) {
            $ingredients = $this->validateIngredients($data['ingredients'], $ingredientErrors);
        } elseif ($ingredientsRequired) {
            $ingredientErrors['ingredients'] = 'Ingredients are required.';
        }

        $errors = array_merge($validator->errors(), $ingredientErrors);

        if (!empty($errors)) {
            throw ApiException::validation($errors);
        }

        $input = $validator->clean();

        if ($ingredients !== null) {
            $input['ingredients'] = $ingredients;
        }

        return $input;
    }

    // Validates a list of {name, measure} items and returns only the lines that passed.
    private function validateIngredients($value, array &$errors): array
    {
        if (!is_array($value)) {
            $errors['ingredients'] = 'Ingredients must be a list.';

            return [];
        }

        if ($value === []) {
            $errors['ingredients'] = 'Ingredients must contain at least one item.';

            return [];
        }

        $clean = [];

        foreach (array_values($value) as $index => $item) {
            $key = 'ingredients.' . $index;

            if (!is_array($item)) {
                $errors[$key] = 'Each ingredient must be an object with a name and an optional measure.';

                continue;
            }

            $valid = true;
            $name = '';

            if (!isset($item['name']) || !is_string($item['name']) || trim($item['name']) === '') {
                $errors[$key . '.name'] = 'Ingredient name is required.';
                $valid = false;
            } else {
                $name = trim($item['name']);

                if (mb_strlen($name, 'UTF-8') > 100) {
                    $errors[$key . '.name'] = 'Ingredient name must be at most 100 characters.';
                    $valid = false;
                }
            }

            $measure = null;

            if (array_key_exists('measure', $item) && $item['measure'] !== null) {
                if (!is_string($item['measure'])) {
                    $errors[$key . '.measure'] = 'Ingredient measure must be a string.';
                    $valid = false;
                } else {
                    $measure = trim($item['measure']);

                    if (mb_strlen($measure, 'UTF-8') > 50) {
                        $errors[$key . '.measure'] = 'Ingredient measure must be at most 50 characters.';
                        $valid = false;
                    }
                }
            }

            if (!$valid) {
                continue;
            }

            $line = ['name' => $name];

            if ($measure !== null && $measure !== '') {
                $line['measure'] = $measure;
            }

            $clean[] = $line;
        }

        return $clean;
    }
}
