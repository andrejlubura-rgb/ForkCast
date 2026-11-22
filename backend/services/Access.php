<?php

// Checks what the identified user (the actor) is allowed to do.
class Access
{
    // True when the actor has the admin role.
    public static function isAdmin(array $actor): bool
    {
        return isset($actor['role']) && $actor['role'] === 'admin';
    }

    // Stops the request with 403 unless the actor is an admin.
    public static function requireAdmin(array $actor): void
    {
        if (!self::isAdmin($actor)) {
            throw ApiException::forbidden();
        }
    }

    // Stops the request with 403 unless the actor is an admin or the owner of the record.
    public static function requireOwnerOrAdmin(array $actor, int $ownerId): void
    {
        if (self::isAdmin($actor)) {
            return;
        }

        if (isset($actor['id']) && (int) $actor['id'] === $ownerId) {
            return;
        }

        throw ApiException::forbidden();
    }
}
