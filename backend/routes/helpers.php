<?php

// Reads the raw JSON object body and returns it as an array; an empty body returns an empty array.
function jsonBody(): array
{
    $raw = file_get_contents('php://input');

    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw);

    if (json_last_error() !== JSON_ERROR_NONE || !is_object($decoded)) {
        throw ApiException::badRequest('Invalid JSON body');
    }

    return json_decode($raw, true);
}
