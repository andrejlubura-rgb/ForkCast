<?php

// Data access for the categories table.
class CategoryDao extends BaseDao
{
    // Writable columns: a category only has a name and an optional description.
    public function __construct()
    {
        parent::__construct('categories', ['name', 'description']);
    }

    // Returns the category with the given name, or null when no row matches.
    public function getByName(string $name): ?array
    {
        $sql = 'SELECT * FROM categories WHERE name = :name';

        $statement = $this->connection->prepare($sql);
        $statement->execute(['name' => $name]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    // Returns the number of recipes that reference the given category.
    public function countRecipes(int $categoryId): int
    {
        $sql = 'SELECT COUNT(*) FROM recipes WHERE category_id = :category_id';

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }
}
