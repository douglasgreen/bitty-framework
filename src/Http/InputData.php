<?php

declare(strict_types=1);

namespace App\Http;

use InvalidArgumentException;

use function is_array;
use function is_numeric;

/**
 * Immutable wrapper for PHP superglobal arrays ($_GET, $_POST).
 * Enforces strict type validation and canonicalization at the boundary.
 */
final readonly class InputData
{
    /**
     * @param array<string, mixed> $data The source array (e.g., $_GET, $_POST).
     */
    public function __construct(private array $data) {}

    /**
     * Checks if a key exists in the input data.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Retrieves a raw value.
     * Use specific typed getters instead where possible.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Retrieves a string value, canonicalized (trimmed).
     */
    public function getString(string $key, string $default = ''): string
    {
        if (!$this->has($key)) {
            return $default;
        }

        $value = $this->data[$key];

        // Strict type checking to prevent unexpected behavior
        if (!is_string($value)) {
            throw new InvalidArgumentException(
                sprintf('Input "%s" expected string, got %s', $key, gettype($value)),
            );
        }

        // Canonicalization: Trim whitespace
        return trim($value);
    }

    /**
     * Retrieves an integer value.
     */
    public function getInt(string $key, int $default = 0): int
    {
        if (!$this->has($key)) {
            return $default;
        }

        $value = $this->data[$key];

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        throw new InvalidArgumentException(
            sprintf('Input "%s" is not a valid integer', $key),
        );
    }

    /**
     * Retrieves a float value.
     */
    public function getFloat(string $key, float $default = 0.0): float
    {
        if (!$this->has($key)) {
            return $default;
        }

        $value = $this->data[$key];

        if (is_float($value)) {
            return $value;
        }

        if (is_int($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        throw new InvalidArgumentException(
            sprintf('Input "%s" is not a valid float', $key),
        );
    }

    /**
     * Retrieves a boolean value.
     * Recognizes "true", "1", "on", "yes" as true (case-insensitive).
     */
    public function getBool(string $key, bool $default = false): bool
    {
        if (!$this->has($key)) {
            return $default;
        }

        $value = $this->data[$key];

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $lower = strtolower($value);
            return in_array($lower, ['true', '1', 'on', 'yes'], true);
        }

        return (bool) $value;
    }

    /**
     * Retrieves an array value.
     */
    public function getArray(string $key, array $default = []): array
    {
        if (!$this->has($key)) {
            return $default;
        }

        $value = $this->data[$key];

        if (!is_array($value)) {
            throw new InvalidArgumentException(
                sprintf('Input "%s" expected array, got %s', $key, gettype($value)),
            );
        }

        return $value;
    }

    /**
     * Returns all input data as an array.
     * Caution: This bypasses type safety guarantees.
     */
    public function all(): array
    {
        return $this->data;
    }
}
