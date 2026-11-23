<?php

// TEMPORARY identification until the JWT middleware in Milestone 4: the caller sends the user id in X-User-Id.
final class CurrentUser
{
    // Returns the identified user without password_hash, or null when the header is missing or unknown.
    public static function get(): ?array
    {
        $header = $_SERVER['HTTP_X_USER_ID'] ?? '';

        if (!is_string($header) || $header === '' || !ctype_digit($header)) {
            return null;
        }

        $user = (new UserDao())->getById((int) $header);

        if ($user === null) {
            return null;
        }

        unset($user['password_hash']);

        return $user;
    }

    // Returns the identified user or stops the request with 401.
    public static function getOrFail(): array
    {
        $user = self::get();

        if ($user === null) {
            throw ApiException::unauthorized();
        }

        return $user;
    }
}
