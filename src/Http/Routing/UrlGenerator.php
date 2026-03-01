<?php

declare(strict_types=1);

namespace DouglasGreen\BittyFramework\Http\Routing;

use InvalidArgumentException;

/**
 * Generates URLs relative to the injected base URL.
 */
final readonly class UrlGenerator
{
    /**
     * @param string $baseUrl The absolute base URL of the application (e.g., http://localhost:8080/index.php)
     * @param string $routeParam The query parameter name for routing
     */
    public function __construct(
        private string $baseUrl,
        private string $routeParam = 'route',
    ) {
        // Validate data format.
        if (!filter_var($baseUrl, FILTER_VALIDATE_URL) && !str_starts_with($baseUrl, '/')) {
            // Allow relative base paths for flexibility but warn conceptually
        }
    }

    /**
     * Generates a URL for a given path.
     *
     * @param string $path The route path (e.g., /user/profile)
     * @param array<string, string> $params Additional query parameters
     */
    public function to(string $path, array $params = []): string
    {
        // Canonicalization.
        if (empty($path) || $path[0] !== '/') {
            throw new InvalidArgumentException('Path must start with a forward slash.');
        }

        $queryParams = [$this->routeParam => $path] + $params;

        return $this->baseUrl . '?' . http_build_query($queryParams);
    }
}
