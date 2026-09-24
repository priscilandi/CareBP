<?php

declare(strict_types=1);

/** Simple JSON API helpers. No session or admin authorization is exposed here. */
final class ApiException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message
    ) {
        parent::__construct($message);
    }
}

function jsonResponse(array $payload, int $status = 200, array $headers = []): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    foreach ($headers as $header) {
        header($header);
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    exit;
}

function apiError(int $status, string $code, string $message): never
{
    jsonResponse(['error' => ['code' => $code, 'message' => $message]], $status);
}

function runApi(callable $handler): never
{
    try {
        $handler();
        // Every successful handler should send its own response.
        apiError(500, 'unexpected_response', 'The server did not produce a response.');
    } catch (ApiException $exception) {
        apiError($exception->status, $exception->errorCode, $exception->getMessage());
    } catch (PDOException $exception) {
        error_log('[CareBP] Database error: ' . $exception->getMessage());
        $connectionError = str_starts_with((string) $exception->getCode(), '08')
            || str_contains($exception->getMessage(), 'could not find driver')
            || str_contains($exception->getMessage(), 'Connection refused')
            || str_contains($exception->getMessage(), 'getaddrinfo');
        apiError(
            $connectionError ? 503 : 500,
            $connectionError ? 'database_unavailable' : 'database_error',
            $connectionError ? 'The database is unavailable. Please try again later.' : 'A database error occurred.'
        );
    } catch (Throwable $exception) {
        error_log('[CareBP] Unexpected error: ' . $exception->getMessage());
        apiError(500, 'internal_error', 'An unexpected server error occurred.');
    }
}

function requireMethod(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== $method) {
        header('Allow: ' . $method);
        throw new ApiException(405, 'method_not_allowed', "This endpoint only supports $method.");
    }
}

function queryString(string $key, int $maxBytes = 120): string
{
    $value = $_GET[$key] ?? '';
    if (!is_string($value)) {
        throw new ApiException(422, 'invalid_query', "The $key filter must be text.");
    }
    $value = trim($value);
    if (strlen($value) > $maxBytes) {
        throw new ApiException(422, 'invalid_query', "The $key filter is too long.");
    }
    return $value;
}

function positiveInt(mixed $value, string $name, int $max = 1000000): int
{
    if (!is_int($value) && !is_string($value)) {
        throw new ApiException(422, 'invalid_field', "$name must be a positive integer.");
    }
    $raw = (string) $value;
    if (!ctype_digit($raw) || (int) $raw < 1 || strlen($raw) > 10 || (int) $raw > $max) {
        throw new ApiException(422, 'invalid_field', "$name must be between 1 and $max.");
    }
    return (int) $raw;
}

/** SQL LIKE with explicit ! escape, so searches treat %, _, and ! literally. */
function likePattern(string $term): string
{
    return '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term) . '%';
}

function jsonBody(int $maxBytes = 8192): array
{
    $type = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
    if ($type !== 'application/json') {
        throw new ApiException(415, 'unsupported_media_type', 'Send an application/json request.');
    }
    if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $maxBytes) {
        throw new ApiException(413, 'payload_too_large', 'The submitted form is too large.');
    }

    $body = file_get_contents('php://input', false, null, 0, $maxBytes + 1);
    if ($body === false || strlen($body) > $maxBytes) {
        throw new ApiException(413, 'payload_too_large', 'The submitted form is too large.');
    }

    try {
        $decoded = json_decode($body, false, 12, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        throw new ApiException(400, 'invalid_json', 'The request body must contain valid JSON.');
    }

    if (!$decoded instanceof stdClass) {
        throw new ApiException(400, 'invalid_json', 'The request body must be a JSON object.');
    }
    return (array) $decoded;
}

function formText(array $body, string $key, int $maxBytes, bool $required = false): ?string
{
    $value = $body[$key] ?? null;
    if ($value === null || $value === '') {
        if ($required) {
            throw new ApiException(422, 'invalid_field', "$key is required.");
        }
        return null;
    }
    if (!is_string($value)) {
        throw new ApiException(422, 'invalid_field', "$key must be text.");
    }
    $value = trim($value);
    if ($required && $value === '') {
        throw new ApiException(422, 'invalid_field', "$key is required.");
    }
    if (strlen($value) > $maxBytes || strpbrk($value, "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0B\x0C\x0E\x0F") !== false) {
        throw new ApiException(422, 'invalid_field', "$key contains invalid characters or is too long.");
    }
    return $value === '' ? null : $value;
}

function usState(?string $value): string
{
    if ($value === null || preg_match('/^[a-zA-Z]{2}$/D', $value) !== 1) {
        throw new ApiException(422, 'invalid_field', 'state must be a two-letter US state abbreviation.');
    }
    return strtoupper($value);
}

function websiteUrl(?string $value): ?string
{
    if ($value === null) {
        return null;
    }
    if (filter_var($value, FILTER_VALIDATE_URL) === false || !in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)) {
        throw new ApiException(422, 'invalid_field', 'website must be a valid http:// or https:// URL.');
    }
    return $value;
}
