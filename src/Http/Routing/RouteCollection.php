<?php

declare(strict_types=1);

namespace DouglasGreen\BittyFramework\Http\Routing;

use InvalidArgumentException;

/**
 * Centralized source of truth for routing.
 */
final class RouteCollection
{
    /** @var array<string, Route> */
    private array $routes = [];

    /**
     * Adds a route to the collection.
     */
    public function add(Route $route): void
    {
        // Validate input constraints.
        if (empty($route->path) || $route->path[0] !== '/') {
            throw new InvalidArgumentException('Route path must start with a forward slash.');
        }

        // Key allows checking for conflicts or specific lookups
        $key = sprintf('%s %s', $route->method, $route->path);
        $this->routes[$key] = $route;
    }

    /**
     * Retrieves all routes.
     *
     * @return array<string, Route>
     */
    public function getAll(): array
    {
        return $this->routes;
    }

    /**
     * Helper to create a GET route.
     */
    public function get(string $path, callable $handler): void
    {
        $this->add(new Route('GET', $path, $handler));
    }

    /**
     * Helper to create a POST route.
     */
    public function post(string $path, callable $handler): void
    {
        $this->add(new Route('POST', $path, $handler));
    }
}
