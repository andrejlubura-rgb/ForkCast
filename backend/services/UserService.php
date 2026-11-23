<?php

// Business rules and validation for users; the only layer that calls UserDao.
class UserService
{
    // DAO used for every user query.
    private UserDao $dao;

    // Creates the DAO used by this service.
    public function __construct()
    {
        $this->dao = new UserDao();
    }

    // Returns one page of users without password_hash; admins only.
    public function getAll(array $actor, int $limit, int $offset): array
    {
        Access::requireAdmin($actor);

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

        return $this->dao->getAllPublic($limit, $offset);
    }

    // Returns one user without password_hash for that same user or an admin.
    public function getById(array $actor, int $id): array
    {
        // A user record belongs to the user with that id, so the access rule is checked before the lookup.
        Access::requireOwnerOrAdmin($actor, $id);

        return $this->findPublic($id);
    }

    // Registers a new user; the role is always "user" and the email must be unique.
    public function create(array $data): array
    {
        $input = $this->validate($data, true);

        if ($this->dao->getByEmail($input['email']) !== null) {
            throw ApiException::badRequest('A user with this email already exists.');
        }

        // The client can never choose a role or send a password hash; the password is hashed here.
        $input['password_hash'] = password_hash($input['password'], PASSWORD_DEFAULT);
        $input['role'] = 'user';
        unset($input['password']);

        $id = $this->dao->insert($input);

        return $this->findPublic($id);
    }

    // Updates a user; that same user or an admin, with the password optional and re-hashed.
    public function update(array $actor, int $id, array $data): array
    {
        Access::requireOwnerOrAdmin($actor, $id);

        $existing = $this->dao->getById($id);

        if ($existing === null) {
            throw ApiException::notFound('User not found');
        }

        // Only an admin may change a role: a non-admin who sends a different role is stopped here.
        if (array_key_exists('role', $data) && !Access::isAdmin($actor)
            && $data['role'] !== $existing['role']) {
            throw ApiException::forbidden('Only an admin can change a role');
        }

        $input = $this->validate($data, false);

        // A role reaches this point only from an admin or when it stays the same.
        if (array_key_exists('role', $data)) {
            $input = array_merge($input, $this->validateRole($data));
        }

        $emailOwner = $this->dao->getByEmail($input['email']);

        if ($emailOwner !== null && (int) $emailOwner['id'] !== $id) {
            throw ApiException::badRequest('A user with this email already exists.');
        }

        if (array_key_exists('password', $input)) {
            $input['password_hash'] = password_hash($input['password'], PASSWORD_DEFAULT);
            unset($input['password']);
        }

        $this->dao->update($id, $input);

        return $this->findPublic($id);
    }

    // Deletes a user; admins only, with a clear 400 when a database constraint blocks it.
    public function delete(array $actor, int $id): void
    {
        Access::requireAdmin($actor);

        if ($this->dao->getById($id) === null) {
            throw ApiException::notFound('User not found');
        }

        try {
            $this->dao->delete($id);
        } catch (PDOException $exception) {
            // SQLSTATE 23000 is a constraint violation: a recipe of this user is used in a plan, so it cannot be removed.
            if ((string) $exception->getCode() === '23000') {
                throw ApiException::badRequest(
                    'This user cannot be deleted because their recipes are used in other users\' plans.'
                );
            }

            throw $exception;
        }
    }

    // Returns one user without password_hash, or throws 404 when it does not exist.
    private function findPublic(int $id): array
    {
        $user = $this->dao->getById($id);

        if ($user === null) {
            throw ApiException::notFound('User not found');
        }

        unset($user['password_hash']);

        return $user;
    }

    // Validates the name, email and password fields; the password is required on POST and optional on PUT.
    private function validate(array $data, bool $passwordRequired): array
    {
        $validator = new Validator($data);
        $validator->string('first_name', 1, 50);
        $validator->string('last_name', 1, 50);
        $validator->email('email', 100);
        $validator->password('password', 8, 72, $passwordRequired);
        $validator->throwIfInvalid();

        return $validator->clean();
    }

    // Validates an optional role change and returns only the fields that passed.
    private function validateRole(array $data): array
    {
        $validator = new Validator($data);
        $validator->inList('role', ['admin', 'user'], false);
        $validator->throwIfInvalid();

        return $validator->clean();
    }
}
