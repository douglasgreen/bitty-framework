<?php

declare(strict_types=1);

namespace App\Http\Response;

use InvalidArgumentException;
use function header;
use function headers_sent;
use function in_array;
use function is_string;

/**
 * Immutable base response using readonly properties.
 */
class Response implements ResponseInterface
{
    protected readonly string $body;

    /**
     * @param string $body The response content
     * @param int $statusCode HTTP status code (100-599)
     * @param array<string, string|string[]> $headers Headers array
     */
    public function __construct(
        string $body = '',
        protected int $statusCode = 200,
        protected array $headers = []
    ) {
        $this->body = $body;
        $this->validateStatusCode($statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Returns an instance with the specified header.
     */
    public function withHeader(string $name, string|array $value): static
    {
        $new = clone $this;
        $new->headers[$name] = $value;
        return $new;
    }

    /**
     * Must ensure correct HTTP status codes.
     */
    protected function validateStatusCode(int $code): void
    {
        if ($code < 100 || $code > 599) {
            throw new InvalidArgumentException(
                sprintf('Invalid HTTP status code: %d', $code)
            );
        }
    }

    /**
     * Sends headers and content to the output buffer.
     */
    public function send(): void
    {
        if (headers_sent()) {
            // In production, this should likely trigger a specific exception or log entry.
            // For safety in this context, we simply output the body.
            echo $this->body;
            return;
        }

        // Set status line (PHP_SAPI detection handles CGI vs Module)
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $values) {
            $values = (array) $values;
            foreach ($values as $value) {
                header("{$name}: {$value}", false);
            }
        }

        echo $this->body;
    }
}
