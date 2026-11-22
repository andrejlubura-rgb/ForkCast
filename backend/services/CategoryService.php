<?php

// Business rules and validation for categories; the only layer that calls CategoryDao.
class CategoryService
{
    // DAO used for every category query.
    private CategoryDao $dao;

    // Creates the DAO used by this service.
    public function __construct()
    {
        $this->dao = new CategoryDao();
    }

    // Returns every category for an identified user, without paging.
    public function getAll(array $actor): array
    {
        return $this->dao->getAll(100, 0);
    }

    // Returns one category or throws 404 when it does not exist.
    public function getById(array $actor, int $id): array
    {
        $category = $this->dao->getById($id);

        if ($category === null) {
            throw ApiException::notFound('Category not found');
        }

        return $category;
    }

    // Creates a category; admins only, with a unique name.
    public function create(array $actor, array $data): array
    {
        Access::requireAdmin($actor);

        $input = $this->validate($data);

        if ($this->dao->getByName($input['name']) !== null) {
            throw ApiException::badRequest('A category with this name already exists.');
        }

        $id = $this->dao->insert($input);

        return $this->getById($actor, $id);
    }

    // Updates a category; admins only, with a unique name.
    public function update(array $actor, int $id, array $data): array
    {
        Access::requireAdmin($actor);

        if ($this->dao->getById($id) === null) {
            throw ApiException::notFound('Category not found');
        }

        $input = $this->validate($data);
        $existing = $this->dao->getByName($input['name']);

        if ($existing !== null && (int) $existing['id'] !== $id) {
            throw ApiException::badRequest('A category with this name already exists.');
        }

        $this->dao->update($id, $input);

        return $this->getById($actor, $id);
    }

    // Deletes a category; admins only and only when no recipe uses it.
    public function delete(array $actor, int $id): void
    {
        Access::requireAdmin($actor);

        if ($this->dao->getById($id) === null) {
            throw ApiException::notFound('Category not found');
        }

        if ($this->dao->countRecipes($id) > 0) {
            throw ApiException::badRequest('A category with recipes cannot be deleted.');
        }

        $this->dao->delete($id);
    }

    // Validates the name and the optional description and returns only the fields that were sent.
    private function validate(array $data): array
    {
        $validator = new Validator($data);
        $validator->string('name', 2, 50);
        $validator->string('description', 0, 255, false);
        $validator->throwIfInvalid();

        return $validator->clean();
    }
}
