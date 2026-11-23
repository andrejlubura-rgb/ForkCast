<?php

// Business rules and validation for meal plans; the only layer that calls MealPlanDao.
class MealPlanService
{
    // DAO used for every plan query.
    private MealPlanDao $dao;

    // DAO used for the planned meals of a plan and for its shopping list.
    private PlannedMealDao $plannedMealDao;

    // Creates the DAOs used by this service.
    public function __construct()
    {
        $this->dao = new MealPlanDao();
        $this->plannedMealDao = new PlannedMealDao();
    }

    // Returns one page of plans: a user sees only their own plans, an admin sees every plan.
    public function getAll(array $actor, int $limit, int $offset): array
    {
        // Keeps paging inside the range from docs/API.md: limit 1-100 (default 20) and offset 0 or more.
        if ($limit < 1) {
            $limit = 20;
        }

        if ($limit > 100) {
            $limit = 100;
        }

        if ($offset < 0) {
            $offset = 0;
        }

        // The admin overview is paged in SQL; one user's plans are paged here because the DAO returns them whole.
        if (Access::isAdmin($actor)) {
            return $this->dao->getAll($limit, $offset);
        }

        return array_slice($this->dao->getByUserId((int) $actor['id']), $offset, $limit);
    }

    // Returns one plan with its planned meals for its owner or an admin, or throws 404 or 403.
    public function getById(array $actor, int $id): array
    {
        $plan = $this->findPlan($id);

        Access::requireOwnerOrAdmin($actor, (int) $plan['user_id']);

        $plan['meals'] = $this->plannedMealDao->getByPlanId($id);

        return $plan;
    }

    // Creates a plan for the caller; the week must start on a Monday and be free for that user.
    public function create(array $actor, array $data): array
    {
        $input = $this->validate($data);

        $this->requireMonday($input['week_start_date']);

        // user_id always comes from the caller and can never be set by the client.
        $input['user_id'] = (int) $actor['id'];

        $this->requireFreeWeek($input['user_id'], $input['week_start_date']);

        $id = $this->dao->insert($input);

        return $this->getById($actor, $id);
    }

    // Updates a plan for its owner or an admin; the plan's own week does not count as taken.
    public function update(array $actor, int $id, array $data): array
    {
        $existing = $this->findPlan($id);

        Access::requireOwnerOrAdmin($actor, (int) $existing['user_id']);

        $input = $this->validate($data);

        $this->requireMonday($input['week_start_date']);

        // user_id is not part of the validated input, so a plan can never change owner.
        $this->requireFreeWeek((int) $existing['user_id'], $input['week_start_date'], $id);

        $this->dao->update($id, $input);

        return $this->getById($actor, $id);
    }

    // Deletes a plan for its owner or an admin; the database removes its planned meals as well.
    public function delete(array $actor, int $id): void
    {
        $plan = $this->findPlan($id);

        Access::requireOwnerOrAdmin($actor, (int) $plan['user_id']);

        $this->dao->delete($id);
    }

    // Returns the grouped ingredient list of one plan for its owner or an admin.
    public function getShoppingList(array $actor, int $id): array
    {
        $plan = $this->findPlan($id);

        Access::requireOwnerOrAdmin($actor, (int) $plan['user_id']);

        return $this->plannedMealDao->getShoppingList($id);
    }

    // Returns one plan or throws 404 when it does not exist.
    private function findPlan(int $id): array
    {
        $plan = $this->dao->getById($id);

        if ($plan === null) {
            throw ApiException::notFound('Plan not found');
        }

        return $plan;
    }

    // Throws a 400 when the week start date is not a Monday.
    private function requireMonday(string $weekStart): void
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $weekStart);

        if ($date === false || $date->format('N') !== '1') {
            throw ApiException::validation(['week_start_date' => 'Week start date must be a Monday.']);
        }
    }

    // Throws a 400 when the user already has a plan for that week; the plan being updated is ignored.
    private function requireFreeWeek(int $userId, string $weekStart, ?int $ignorePlanId = null): void
    {
        $existing = $this->dao->getByUserAndWeek($userId, $weekStart);

        if ($existing !== null && ($ignorePlanId === null || (int) $existing['id'] !== $ignorePlanId)) {
            throw ApiException::badRequest('A plan for this week already exists.');
        }
    }

    // Validates the name and the week start date and returns only the fields that were sent.
    private function validate(array $data): array
    {
        $validator = new Validator($data);
        $validator->string('name', 1, 100);
        $validator->date('week_start_date');
        $validator->throwIfInvalid();

        return $validator->clean();
    }
}
