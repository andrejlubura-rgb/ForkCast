<?php

// Data access for the meal_plans table.
class MealPlanDao extends BaseDao
{
    // Writable columns: created_at is set by the database, so it is never written here.
    public function __construct()
    {
        parent::__construct('meal_plans', ['user_id', 'name', 'week_start_date']);
    }

    // Returns all plans of one user, newest week first.
    public function getByUserId(int $userId): array
    {
        $sql = 'SELECT * FROM meal_plans WHERE user_id = :user_id ORDER BY week_start_date DESC, id DESC';

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    // Returns the plan of one user for one week, or null when it does not exist.
    public function getByUserAndWeek(int $userId, string $weekStart): ?array
    {
        $sql = 'SELECT * FROM meal_plans WHERE user_id = :user_id AND week_start_date = :week_start_date';

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':week_start_date', $weekStart, PDO::PARAM_STR);
        $statement->execute();
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }
}
