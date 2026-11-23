<?php

// Business rules and validation for planned meals; the only layer that calls PlannedMealDao.
class PlannedMealService
{
    // Meal types allowed by the database enum and docs/API.md.
    private const MEAL_TYPES = ['breakfast', 'lunch', 'dinner', 'snack'];

    // DAO used for every planned meal query.
    private PlannedMealDao $dao;

    // DAO used to find the plan that a planned meal belongs to.
    private MealPlanDao $planDao;

    // DAO used to check that a recipe exists.
    private RecipeDao $recipeDao;

    // Creates the DAOs used by this service.
    public function __construct()
    {
        $this->dao = new PlannedMealDao();
        $this->planDao = new MealPlanDao();
        $this->recipeDao = new RecipeDao();
    }

    // Returns every planned meal of one plan for the owner of the plan or an admin.
    public function getByPlan(array $actor, int $planId): array
    {
        $this->findPlanForActor($actor, $planId);

        return $this->dao->getByPlanId($planId);
    }

    // Adds a meal to a free slot of a plan for the owner of the plan or an admin.
    public function create(array $actor, int $planId, array $data): array
    {
        $this->findPlanForActor($actor, $planId);

        $input = $this->validate($data, 1);

        $this->requireRecipe((int) $input['recipe_id']);
        $this->requireFreeSlot($planId, (int) $input['day_of_week'], $input['meal_type']);

        // plan_id always comes from the route, so a meal can never be added to another plan.
        $input['plan_id'] = $planId;

        $id = $this->dao->insert($input);

        return $this->findMeal($id);
    }

    // Updates a planned meal for the owner of its plan or an admin; its own slot does not count as taken.
    public function update(array $actor, int $id, array $data): array
    {
        $meal = $this->findMeal($id);
        $planId = (int) $meal['plan_id'];

        $this->findPlanForActor($actor, $planId);

        // Servings falls back to the current value when the client does not send it.
        $input = $this->validate($data, (int) $meal['servings']);

        $this->requireRecipe((int) $input['recipe_id']);
        $this->requireFreeSlot($planId, (int) $input['day_of_week'], $input['meal_type'], $id);

        $this->dao->update($id, $input);

        return $this->findMeal($id);
    }

    // Deletes a planned meal for the owner of its plan or an admin.
    public function delete(array $actor, int $id): void
    {
        $meal = $this->findMeal($id);

        $this->findPlanForActor($actor, (int) $meal['plan_id']);

        $this->dao->delete($id);
    }

    // Returns the plan of a planned meal and stops with 404 or 403 when the actor may not change it.
    private function findPlanForActor(array $actor, int $planId): array
    {
        $plan = $this->planDao->getById($planId);

        if ($plan === null) {
            throw ApiException::notFound('Plan not found');
        }

        Access::requireOwnerOrAdmin($actor, (int) $plan['user_id']);

        return $plan;
    }

    // Returns one planned meal or throws 404 when it does not exist.
    private function findMeal(int $id): array
    {
        $meal = $this->dao->getById($id);

        if ($meal === null) {
            throw ApiException::notFound('Planned meal not found');
        }

        return $meal;
    }

    // Throws a 400 when the recipe does not exist.
    private function requireRecipe(int $recipeId): void
    {
        if ($this->recipeDao->getById($recipeId) === null) {
            throw ApiException::validation(['recipe_id' => 'Recipe not found.']);
        }
    }

    // Throws a 400 when another meal already uses the same day and meal type; the meal being updated is ignored.
    private function requireFreeSlot(int $planId, int $day, string $mealType, ?int $ignoreMealId = null): void
    {
        $taken = $this->dao->getBySlot($planId, $day, $mealType);

        if ($taken !== null && ($ignoreMealId === null || (int) $taken['id'] !== $ignoreMealId)) {
            throw ApiException::badRequest('This slot already has a meal.');
        }
    }

    // Validates the recipe, slot and servings; servings falls back to the given value when it is not sent.
    private function validate(array $data, int $defaultServings): array
    {
        $validator = new Validator($data);
        $validator->int('recipe_id', 1, 2147483647);
        $validator->int('day_of_week', 1, 7);
        $validator->inList('meal_type', self::MEAL_TYPES);
        $validator->int('servings', 1, 20, false);
        $validator->throwIfInvalid();

        $input = $validator->clean();

        if (!array_key_exists('servings', $input)) {
            $input['servings'] = $defaultServings;
        }

        return $input;
    }
}
