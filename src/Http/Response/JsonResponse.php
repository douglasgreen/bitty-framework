<?php

declare(strict_types=1);

namespace App\Http\Response;

use JsonException;
use function json_encode;
use const JSON_THROW_ON_ERROR;

/**
 * Standard 7.2 (error-handling.md): Validate and emit JSON conforming to RFC 8259.
 */
final class JsonResponse extends Response
{
    /**
     * @param mixed $data Data to encode as JSON
     * @param int $status HTTP status code
     * @param array<string, string> $headers Additional headers
     */
    public function __construct(
        mixed $data,
        int $status = 200,
        array $headers = []
    ) {
        // Standard 2.1.1 (php.md): Explicit options for json_encode
        // JSON_THROW_ON_ERROR ensures strict compliance and fails fast on invalid data.
        try {
            $body = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $e) {
            // Standard 4.1.3 (error-handling.md): Map infrastructure errors to internal types.
            // Wrapping in runtime exception to satisfy strict types and fail safely.
            throw new \RuntimeException('Failed to encode JSON response: ' . $e->getMessage(), 0, $e);
        }

        // Force Content-Type to application/json
        $headers['Content-Type'] = 'application/json';

        parent::__construct($body, $status, $headers);
    }

    /**
     * Factory for standardized error responses.
     * Standard 4.2.3 (error-handling.md): Structured error responses with codes.
     *
     * @param string $code Stable error code (e.g., VALIDATION_ERROR)
     * @param string $message Human-readable message
     * @param int $status HTTP Status code
     */
    public static function error(string $code, string $message, int $status = 400): self
    {
        return new self(
            [
                'error' => [
                    'code' => $code,
                    'message' => $message,
                ]
            ],
            $status
        );
    }
}
