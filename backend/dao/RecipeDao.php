<?php

// Data access for the recipes table.
class RecipeDao extends BaseDao
{
    // Writable columns: created_at is set by the database, so it is never written here.
    public function __construct()
    {
        parent::__construct('recipes', ['user_id', 'category_id', 'title', 'instructions', 'prep_time_minutes', 'servings', 'image_url', 'source', 'external_id']);
    }

    // Returns one page of recipes with category and author names, filtered only by the values that are not null.
    public function search(?string $text, ?int $categoryId, ?int $userId, int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT r.*, c.name AS category_name, u.first_name AS author_first_name, u.last_name AS author_last_name'
            . ' FROM recipes r'
            . ' JOIN categories c ON c.id = r.category_id'
            . ' JOIN users u ON u.id = r.user_id';

        $conditions = [];
        $params = [];

        if ($text !== null) {
            $conditions[] = 'r.title LIKE :text';
            $params['text'] = '%' . $text . '%';
        }

        if ($categoryId !== null) {
            $conditions[] = 'r.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        if ($userId !== null) {
            $conditions[] = 'r.user_id = :user_id';
            $params['user_id'] = $userId;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY r.created_at DESC, r.id DESC LIMIT :limit OFFSET :offset';

        $statement = $this->connection->prepare($sql);

        foreach ($params as $name => $value) {
            $statement->bindValue(':' . $name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    // Returns one recipe with its category and author names, or null when it does not exist.
    public function getByIdWithDetails(int $id): ?array
    {
        $sql = 'SELECT r.*, c.name AS category_name, u.first_name AS author_first_name, u.last_name AS author_last_name'
            . ' FROM recipes r'
            . ' JOIN categories c ON c.id = r.category_id'
            . ' JOIN users u ON u.id = r.user_id'
            . ' WHERE r.id = :id';

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    // Returns the recipe of one user imported with the given TheMealDB id, or null when it was not imported yet.
    public function getByExternalId(int $userId, string $externalId): ?array
    {
        $sql = 'SELECT * FROM recipes WHERE user_id = :user_id AND external_id = :external_id';

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':external_id', $externalId, PDO::PARAM_STR);
        $statement->execute();
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }
}
