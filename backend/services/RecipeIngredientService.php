<?php

// Business rules and validation for recipe ingredients; the only layer that calls RecipeIngredientDao.
class RecipeIngredientService
{
    // DAO used for every ingredient query.
    private RecipeIngredientDao $dao;

    // DAO used to find the recipe that an ingredient belongs to.
    private RecipeDao $recipeDao;

    // Creates the DAOs used by this service.
    public function __construct()
    {
        $this->dao = new RecipeIngredientDao();
        $this->recipeDao = new RecipeDao();
    }

    // Returns every ingredient line of one recipe for an identified user.
    public function getByRecipe(array $actor, int $recipeId): array
    {
        $this->findRecipe($recipeId);

        return $this->dao->getByRecipeId($recipeId);
    }

    // Adds an ingredient line to a recipe; the owner of the recipe or an admin.
    public function create(array $actor, int $recipeId, array $data): array
    {
        $recipe = $this->findRecipe($recipeId);

        Access::requireOwnerOrAdmin($actor, (int) $recipe['user_id']);

        $input = $this->validate($data);
        $input['recipe_id'] = $recipeId;

        $id = $this->dao->insert($input);

        return $this->findIngredient($id);
    }

    // Updates one ingredient line; the owner of its recipe or an admin.
    public function update(array $actor, int $id, array $data): array
    {
        $ingredient = $this->findIngredient($id);
        $recipe = $this->findRecipe((int) $ingredient['recipe_id']);

        Access::requireOwnerOrAdmin($actor, (int) $recipe['user_id']);

        $input = $this->validate($data);
        $this->dao->update($id, $input);

        return $this->findIngredient($id);
    }

    // Deletes one ingredient line; the owner of its recipe or an admin, and never the last one.
    public function delete(array $actor, int $id): void
    {
        $ingredient = $this->findIngredient($id);
        $recipe = $this->findRecipe((int) $ingredient['recipe_id']);

        Access::requireOwnerOrAdmin($actor, (int) $recipe['user_id']);

        if ($this->dao->countByRecipeId((int) $ingredient['recipe_id']) <= 1) {
            throw ApiException::badRequest('The last ingredient of a recipe cannot be deleted.');
        }

        $this->dao->delete($id);
    }

    // Returns one recipe or throws 404 when it does not exist.
    private function findRecipe(int $recipeId): array
    {
        $recipe = $this->recipeDao->getById($recipeId);

        if ($recipe === null) {
            throw ApiException::notFound('Recipe not found');
        }

        return $recipe;
    }

    // Returns one ingredient line or throws 404 when it does not exist.
    private function findIngredient(int $id): array
    {
        $ingredient = $this->dao->getById($id);

        if ($ingredient === null) {
            throw ApiException::notFound('Ingredient not found');
        }

        return $ingredient;
    }

    // Validates the name and the optional measure and returns only the fields that were sent.
    private function validate(array $data): array
    {
        $validator = new Validator($data);
        $validator->string('name', 1, 100);
        $validator->string('measure', 0, 50, false);
        $validator->throwIfInvalid();

        return $validator->clean();
    }
}
