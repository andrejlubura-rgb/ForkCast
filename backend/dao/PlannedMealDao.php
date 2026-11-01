<?php

// Data access for the planned_meals table.
class PlannedMealDao extends BaseDao
{
    // Writable columns: a planned meal only has a plan, a recipe, a slot and a serving count.
    public function __construct()
    {
        parent::__construct('planned_meals', ['plan_id', 'recipe_id', 'day_of_week', 'meal_type', 'servings']);
    }

    // Returns every planned meal of one plan with the recipe title and image, ordered by day and meal type.
    public function getByPlanId(int $planId): array
    {
        $sql = 'SELECT pm.*, r.title AS recipe_title, r.image_url AS recipe_image_url'
            . ' FROM planned_meals pm'
            . ' JOIN recipes r ON r.id = pm.recipe_id'
            . ' WHERE pm.plan_id = :plan_id'
            . ' ORDER BY pm.day_of_week, pm.meal_type';

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':plan_id', $planId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    // Returns the planned meal in one slot of a plan, or null when the slot is empty.
    public function getBySlot(int $planId, int $day, string $mealType): ?array
    {
        $sql = 'SELECT * FROM planned_meals'
            . ' WHERE plan_id = :plan_id AND day_of_week = :day_of_week AND meal_type = :meal_type';

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':plan_id', $planId, PDO::PARAM_INT);
        $statement->bindValue(':day_of_week', $day, PDO::PARAM_INT);
        $statement->bindValue(':meal_type', $mealType, PDO::PARAM_STR);
        $statement->execute();
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    // Counts how many planned meals use one recipe.
    public function countByRecipeId(int $recipeId): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM planned_meals WHERE recipe_id = :recipe_id';

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':recipe_id', $recipeId, PDO::PARAM_INT);
        $statement->execute();
        $row = $statement->fetch();

        return (int) $row['total'];
    }

    // Returns the ingredients of all recipes in one plan, grouped by name and sorted alphabetically.
    public function getShoppingList(int $planId): array
    {
        $sql = 'SELECT ri.name,'
            . " GROUP_CONCAT(DISTINCT ri.measure SEPARATOR ', ') AS measures,"
            . ' COUNT(*) AS times_used'
            . ' FROM planned_meals pm'
            . ' JOIN recipe_ingredients ri ON ri.recipe_id = pm.recipe_id'
            . ' WHERE pm.plan_id = :plan_id'
            . ' GROUP BY ri.name'
            . ' ORDER BY ri.name';

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':plan_id', $planId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
