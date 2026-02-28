<?php

declare(strict_types=1);

namespace App\Http\Routing;

use App\Http\Request;
use App\Http\Response\Response;
use App\Http\Response\JsonResponse;

/**
 * Separates routing logic from request handling.
 */
final class Router
{
    private string $routeParam;

    /**
     * @param RouteCollection $routes The defined routes
     * @param string $routeParam The query parameter name used for routing (default 'route')
     */
    public function __construct(
        private RouteCollection $routes,
        string $routeParam = 'route'
    ) {
        $this->routeParam = $routeParam;
    }

    /**
     * Matches the request and dispatches the handler.
     */
    public function dispatch(Request $request): Response
    {
        // Retrieve path from query param, defaulting to '/' if missing.
        $path = $request->query->getString($this->routeParam, '/');

        // Normalize path: ensure it starts with /
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . $path;
        }

        $method = $request->server->getMethod();

        // 1. Attempt exact match
        $route = $this->matchRoute($method, $path);

        if ($route) {
            return call_user_func($route->handler, $request);
        }

        // 2. Check for Method Not Allowed (405)
        // If route exists for other methods, return 405.
        foreach ($this->routes->all() as $existingRoute) {
            if ($existingRoute->path === $path) {
                return new JsonResponse(
                    ['error' => 'Method Not Allowed', 'code' => 'HTTP_405'],
                    405
                );
            }
        }

        // 3. Check for dynamic parameters (Simple placeholder matching: {param})
        foreach ($this->routes->all() as $existingRoute) {
            $params = $this->matchDynamic($existingRoute->path, $path);
            if ($params !== null && $existingRoute->method === $method) {
                // Inject params into request attributes (simulated here via cloning or passing array)
                // For this simple wrapper, we pass params as a second argument to handler
                // or modify Request. Let's modify Request to allow attributes.
                // NOTE: The Request class defined previously is readonly.
                // To handle this cleanly without reflection hacks, we pass params to handler.
                // OR we assume the handler extracts params from the path.
                // Better approach: The Router parses params and passes them to the handler.

                // Since we cannot modify the readonly Request easily without a "withAttribute" method,
                // we will define the handler signature expectation: handler(Request $request, array $params)
                return call_user_func($existingRoute->handler, $request, $params);
            }
        }

        // 4. Not Found (404)
        return new JsonResponse(
            ['error' => 'Not Found', 'code' => 'HTTP_404'],
            404
        );
    }

    private function matchRoute(string $method, string $path): ?Route
    {
        $key = sprintf('%s %s', $method, $path);
        return $this->routes->all()[$key] ?? null;
    }

    /**
     * Simple dynamic matching for placeholders like {id}.
     * Does not support regex constraints for this implementation to keep it standard-compliant and simple.
     *
     * @return array<string, string>|null
     */
    private function matchDynamic(string $pattern, string $path): ?array
    {
        // Convert pattern '/user/{id}' to regex '#^/user/([^/]+)$#'
        if (strpos($pattern, '{') === false) {
            return null;
        }

        $regex = '#^' . preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '([^/]+)', $pattern) . '$#';

        if (preg_match($regex, $path, $matches)) {
            // Extract param names
            $paramNames = [];
            preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $pattern, $nameMatches);

            $params = [];
            foreach ($nameMatches[1] as $index => $name) {
                $params[$name] = $matches[$index + 1];
            }
            return $params;
        }

        return null;
    }
}
