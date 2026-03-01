<?php

declare(strict_types=1);

namespace DouglasGreen\BittyFramework\Http\Routing;

/**
 * Immutable value object for route configuration.
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
        public $handler,
    ) {}
}
