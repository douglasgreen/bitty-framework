<?php

declare(strict_types=1);

namespace DouglasGreen\BittyFramework\Http\Response;

use JsonException;
use RuntimeException;

use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Validate and emit JSON conforming to RFC 8259.
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
        array $headers = [],
    ) {
        // JSON_THROW_ON_ERROR ensures strict compliance and fails fast on invalid data.
        try {
            $body = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $jsonException) {
            // Wrapping in runtime exception to satisfy strict types and fail safely.
            throw new RuntimeException('Failed to encode JSON response: ' . $jsonException->getMessage(), 0, $jsonException);
        }

        // Force Content-Type to application/json
        $headers['Content-Type'] = 'application/json';

        parent::__construct($body, $status, $headers);
    }

    /**
     * Factory for standardized error responses.
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
                ],
            ],
            $status,
        );
    }
}
