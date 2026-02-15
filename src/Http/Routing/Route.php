<?php

declare(strict_types=1);

namespace App\Http\Routing;

/**
 * Standard 2.2.3 (php.md): Immutable value object for route configuration.
 */
final readonly class Route
{
    /**
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $path Path pattern (e.g., /user/{id})
     * @param callable $handler The controller/action to execute
     */
    public function __construct(
        public string $method,
        public string $path,
        public $handler
    ) {}
}
