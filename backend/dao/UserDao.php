<?php

// Data access for the users table.
class UserDao extends BaseDao
{
    // Writable columns: passwords are stored hashed, so password_hash is only ever written through the service.
    public function __construct()
    {
        parent::__construct('users', ['first_name', 'last_name', 'email', 'password_hash', 'role']);
    }

    // Returns the full user row for the given email, including password_hash; used for authentication only.
    public function getByEmail(string $email): ?array
    {
        $sql = 'SELECT * FROM users WHERE email = :email';

        $statement = $this->connection->prepare($sql);
        $statement->execute(['email' => $email]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    // Returns one page of users without password_hash, for list views.
    public function getAllPublic(int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT id, first_name, last_name, email, role, created_at'
            . ' FROM users ORDER BY id LIMIT :limit OFFSET :offset';

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
