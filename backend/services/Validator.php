<?php

// Validates request input field by field, keeps one error per field and returns the cleaned values.
class Validator
{
    // Raw input array, usually the decoded JSON request body.
    private array $input;

    // Field name => error message; only the first error per field is kept.
    private array $errors = [];

    // Field name => cleaned value for every validated field that passed.
    private array $clean = [];

    // Stores the input array that will be validated.
    public function __construct(array $input)
    {
        $this->input = $input;
    }

    // Validates a required or optional trimmed string and its character length.
    public function string(string $field, int $min, int $max, bool $required = true): self
    {
        $value = $this->readString($field, $required);

        if ($value === null) {
            return $this;
        }

        $length = mb_strlen($value, 'UTF-8');

        if ($length < $min || $length > $max) {
            $this->addError($field, $this->label($field) . ' must be between ' . $min . ' and ' . $max . ' characters.');

            return $this;
        }

        $this->clean[$field] = $value;

        return $this;
    }

    // Validates an integer field and its allowed range.
    public function int(string $field, int $min, int $max, bool $required = true): self
    {
        $value = $this->readInt($field, $required);

        if ($value === null) {
            return $this;
        }

        if ($value < $min || $value > $max) {
            $this->addError($field, $this->label($field) . ' must be between ' . $min . ' and ' . $max . '.');

            return $this;
        }

        $this->clean[$field] = $value;

        return $this;
    }

    // Validates a required email address and its maximum length.
    public function email(string $field, int $max): self
    {
        $value = $this->readString($field, true);

        if ($value === null) {
            return $this;
        }

        if (mb_strlen($value, 'UTF-8') > $max) {
            $this->addError($field, $this->label($field) . ' must be at most ' . $max . ' characters.');

            return $this;
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->addError($field, $this->label($field) . ' must be a valid email address.');

            return $this;
        }

        $this->clean[$field] = $value;

        return $this;
    }

    // Validates a required password by length only; the value is never trimmed or changed.
    public function password(string $field, int $min = 8, int $max = 72, bool $required = true): self
    {
        $value = $this->readString($field, $required, false);

        if ($value === null) {
            return $this;
        }

        $length = mb_strlen($value, 'UTF-8');

        if ($length < $min || $length > $max) {
            $this->addError($field, $this->label($field) . ' must be between ' . $min . ' and ' . $max . ' characters.');

            return $this;
        }

        $this->clean[$field] = $value;

        return $this;
    }

    // Validates a value against a fixed list of allowed strings.
    public function inList(string $field, array $allowed, bool $required = true): self
    {
        $value = $this->readString($field, $required);

        if ($value === null) {
            return $this;
        }

        if (!in_array($value, $allowed, true)) {
            $this->addError($field, $this->label($field) . ' must be one of: ' . implode(', ', $allowed) . '.');

            return $this;
        }

        $this->clean[$field] = $value;

        return $this;
    }

    // Validates a required date in YYYY-MM-DD format that exists on the calendar.
    public function date(string $field): self
    {
        $value = $this->readString($field, true);

        if ($value === null) {
            return $this;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts) !== 1
            || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            $this->addError($field, $this->label($field) . ' must be a valid date (YYYY-MM-DD).');

            return $this;
        }

        $this->clean[$field] = $value;

        return $this;
    }

    // Validates an optional URL with an http or https scheme and its maximum length.
    public function url(string $field, int $max, bool $required = false): self
    {
        $value = $this->readString($field, $required);

        if ($value === null) {
            return $this;
        }

        if (mb_strlen($value, 'UTF-8') > $max) {
            $this->addError($field, $this->label($field) . ' must be at most ' . $max . ' characters.');

            return $this;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        if (filter_var($value, FILTER_VALIDATE_URL) === false || ($scheme !== 'http' && $scheme !== 'https')) {
            $this->addError($field, $this->label($field) . ' must be a valid http or https URL.');

            return $this;
        }

        $this->clean[$field] = $value;

        return $this;
    }

    // True when no field has an error.
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    // Returns field name => error message pairs.
    public function errors(): array
    {
        return $this->errors;
    }

    // Returns only the validated fields that were present, with strings trimmed and integers cast.
    public function clean(): array
    {
        return $this->clean;
    }

    // Throws a 400 validation ApiException when any field has an error.
    public function throwIfInvalid(): void
    {
        if (!empty($this->errors)) {
            throw ApiException::validation($this->errors);
        }
    }

    // Reads a string value, trims it when asked, and returns null when it is missing or invalid.
    private function readString(string $field, bool $required, bool $trim = true): ?string
    {
        if (!array_key_exists($field, $this->input) || $this->input[$field] === null) {
            if ($required) {
                $this->addError($field, $this->label($field) . ' is required.');
            }

            return null;
        }

        $value = $this->input[$field];

        if (!is_string($value)) {
            $this->addError($field, $this->label($field) . ' must be a string.');

            return null;
        }

        if ($trim) {
            $value = trim($value);
        }

        if ($value === '') {
            if ($required) {
                $this->addError($field, $this->label($field) . ' is required.');
            }

            return null;
        }

        return $value;
    }

    // Reads an integer value, accepting integer numbers and integer strings.
    private function readInt(string $field, bool $required): ?int
    {
        if (!array_key_exists($field, $this->input) || $this->input[$field] === null) {
            if ($required) {
                $this->addError($field, $this->label($field) . ' is required.');
            }

            return null;
        }

        $value = $this->input[$field];

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^[+-]?\d+$/', trim($value)) === 1) {
            return (int) trim($value);
        }

        $this->addError($field, $this->label($field) . ' must be an integer.');

        return null;
    }

    // Stores the first error message for a field and ignores later ones.
    private function addError(string $field, string $message): void
    {
        if (!array_key_exists($field, $this->errors)) {
            $this->errors[$field] = $message;
        }
    }

    // Turns a snake_case field name into a readable label for error messages.
    private function label(string $field): string
    {
        return ucfirst(str_replace('_', ' ', $field));
    }
}
