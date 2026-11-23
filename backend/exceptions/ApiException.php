<?php

// Carries an HTTP status code and optional field errors from the services to the route layer.
class ApiException extends Exception
{
    // Field name => error message pairs that explain a validation failure.
    private array $errors;

    // Stores the message, the HTTP status as the exception code and the field errors.
    public function __construct(string $message, int $statusCode, array $errors = [])
    {
        parent::__construct($message, $statusCode);
        $this->errors = $errors;
    }

    // Returns the field errors, or an empty array when there are none.
    public function getErrors(): array
    {
        return $this->errors;
    }

    // 400: the input is invalid or a business rule is violated.
    public static function badRequest(string $message): self
    {
        return new self($message, 400);
    }

    // 400: one or more fields failed validation.
    public static function validation(array $errors): self
    {
        return new self('Validation failed', 400, $errors);
    }

    // 401: no identified user.
    public static function unauthorized(string $message = 'Authentication required'): self
    {
        return new self($message, 401);
    }

    // 403: the identified user is not allowed to perform the action.
    public static function forbidden(string $message = 'You are not allowed to do this'): self
    {
        return new self($message, 403);
    }

    // 404: the requested record does not exist.
    public static function notFound(string $message = 'Not found'): self
    {
        return new self($message, 404);
    }

    // 503: an external service, such as TheMealDB, is unavailable.
    public static function unavailable(string $message): self
    {
        return new self($message, 503);
    }
}
