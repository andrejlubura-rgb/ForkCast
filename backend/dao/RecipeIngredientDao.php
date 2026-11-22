<?php

// Data access for the recipe_ingredients table.
class RecipeIngredientDao extends BaseDao
{
    // Writable columns: an ingredient line only has a recipe, a name and an optional measure.
    public function __construct()
    {
        parent::__construct('recipe_ingredients', ['recipe_id', 'name', 'measure']);
    }

    // Returns all ingredient lines of one recipe in insertion order.
    public function getByRecipeId(int $recipeId): array
    {
        $sql = 'SELECT * FROM recipe_ingredients WHERE recipe_id = :recipe_id ORDER BY id';

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':recipe_id', $recipeId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    // Deletes every ingredient line of one recipe and returns the number of deleted rows.
    public function deleteByRecipeId(int $recipeId): int
    {
        $sql = 'DELETE FROM recipe_ingredients WHERE recipe_id = :recipe_id';

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':recipe_id', $recipeId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount();
    }
}
