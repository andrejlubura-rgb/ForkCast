<?php

// Shared PDO helpers and basic CRUD operations for all DAOs.
abstract class BaseDao
{
    // Shared PDO connection obtained from Database.
    protected PDO $connection;

    // Table name, provided by the child DAO and never taken from request data.
    protected string $table;

    // Columns that insert() and update() are allowed to write.
    protected array $columns;

    // Stores the table and its writable columns and gets the shared connection.
    public function __construct(string $table, array $columns)
    {
        $this->table = $table;
        $this->columns = $columns;
        $this->connection = Database::connect();
    }

    // Runs a SELECT that returns multiple rows, binding every value as a parameter.
    protected function query(string $sql, array $params = []): array
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    // Runs a SELECT that returns at most one row, or null when nothing is found.
    protected function queryOne(string $sql, array $params = []): ?array
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();
        
        return $row === false ? null : $row;
    }

    // Returns one page of rows, with LIMIT and OFFSET bound as integers.
    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT * FROM ' . $this->table . ' LIMIT :limit OFFSET :offset';

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    // Returns the row with the given primary key, or null when it does not exist.
    public function getById(int $id): ?array
    {
        $sql = 'SELECT * FROM ' . $this->table . ' WHERE id = :id';

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    // Inserts the whitelisted columns and returns the id of the new row.
    public function insert(array $data): int
    {
        $data = array_intersect_key($data, array_flip($this->columns));

        if (empty($data)) {
            throw new InvalidArgumentException('No valid columns to insert.');
        }

        $columnNames = array_keys($data);
        $placeholders = array_map(function ($column) {
            return ':' . $column;
        }, $columnNames);

        $sql = 'INSERT INTO ' . $this->table
            . ' (' . implode(', ', $columnNames) . ')'
            . ' VALUES (' . implode(', ', $placeholders) . ')';

        $statement = $this->connection->prepare($sql);
        $statement->execute($data);

        return (int) $this->connection->lastInsertId();
    }

    // Updates the whitelisted columns of one row; returns false when nothing valid was sent.
    public function update(int $id, array $data): bool
    {
        $data = array_intersect_key($data, array_flip($this->columns));

        if (empty($data)) {
            return false;
        }

        $assignments = [];
        foreach (array_keys($data) as $column) {
            $assignments[] = $column . ' = :' . $column;
        }

        $params = $data;
        $params['id'] = $id;

        $sql = 'UPDATE ' . $this->table
            . ' SET ' . implode(', ', $assignments)
            . ' WHERE id = :id';

        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return true;
    }

    // Deletes the row with the given primary key; returns true only when a row was deleted.
    public function delete(int $id): bool
    {
        $sql = 'DELETE FROM ' . $this->table . ' WHERE id = :id';

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount() > 0;
    }
}
