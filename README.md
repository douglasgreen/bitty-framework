# Bitty Framework

Bitty is a PHP microframework.

A strict, immutable, and object-oriented wrapper library for PHP HTTP superglobals, responses, and routing. This library is designed to decouple application logic from the global state (`$_GET`, `$_POST`, etc.) and enforce modern engineering standards including PHP 8.3+ strict typing, immutability, and secure defaults.

## Features

*   **Immutable Wrappers**: Encapsulates superglobals in immutable, readonly value objects.
*   **Type Safety**: Enforces `declare(strict_types=1)` and provides strictly typed getters (`getInt`, `getString`, etc.) to validate input at the system boundary.
*   **Standard Compliance**: Aligns with PSR-12 coding standards and RFC 7231/7232 HTTP semantics.
*   **Security First**: Centralizes input validation, prevents direct access to global state, and includes secure defaults for Response headers and session handling.
*   **Zero-Config Routing**: Includes a router that works without server-level URL rewriting, utilizing a query parameter strategy ideal for legacy systems or restricted environments.

## Requirements

*   **PHP**: 8.3+
*   **Extensions**: `json` (standard)

## Installation

Install via Composer:

```bash
composer require app/http-toolkit
```

## Architecture

The library follows a **Layered Architecture** pattern:

1.  **Infrastructure Layer**: `Request` and `ServerData` wrappers handle the raw input (superglobals).
2.  **Application Layer**: `Router` and `UrlGenerator` orchestrate the application flow.
3.  **Presentation Layer**: `Response` objects format the output.

## Usage Guide

### 1. Handling Requests

The `Request` object acts as the single entry point for all user input. It separates query parameters, body data, server information, and file uploads.

**Creating the Request:**

```php
use App\Http\Request;

// Standard 1.1.2: Domain logic depends on wrappers, not superglobals directly.
$request = Request::fromGlobals();
```

**Accessing Input Data:**

Use the strictly typed getters to validate data immediately. This prevents type coercion bugs downstream.

```php
// Accessing GET parameters (?id=123)
$id = $request->query->getInt('id'); // Returns (int) 123

// Accessing POST data with a default value
$email = $request->body->getString('email', '');

// Accessing Server data
$ip = $request->server->getClientIp();
$isHttps = $request->server->isHttps();
```

**Handling File Uploads:**

```php
$avatar = $request->getFile('avatar');

if ($avatar && $avatar->isValid()) {
    $avatar->moveTo('/var/www/uploads/' . $avatar->name);
}
```

### 2. Routing

The `Router` maps request paths to handlers. It uses a query parameter (default `route`) to determine the path, removing the need for `mod_rewrite` or Nginx configuration.

**Defining Routes:**

```php
use App\Http\Routing\RouteCollection;
use App\Http\Routing\Route;

$routes = new RouteCollection();

// Simple closure route
$routes->get('/', function (Request $request) {
    return new \App\Http\Response\Response('Welcome Home');
});

// Route with dynamic parameters
$routes->get('/user/{id}', function (Request $request, array $params) {
    $userId = $params['id'];
    return new \App\Http\Response\JsonResponse(['id' => $userId]);
});
```

**Dispatching:**

```php
use App\Http\Routing\Router;

$router = new Router($routes);
$response = $router->dispatch($request);
```

### 3. Generating URLs

The `UrlGenerator` requires an injected Base URL, ensuring all generated links are absolute and correct regardless of the server configuration.

```php
use App\Http\Routing\UrlGenerator;

// Inject the base URL (usually from config)
$urlGenerator = new UrlGenerator('http://localhost:8080/index.php');

// Generates: http://localhost:8080/index.php?route=/user/profile&sort=asc
$url = $urlGenerator->to('/user/profile', ['sort' => 'asc']);
```

### 4. Sending Responses

Response objects handle HTTP headers and body formatting. All response objects are immutable.

```php
use App\Http\Response\JsonResponse;
use App\Http\Response\HtmlResponse;
use App\Http\Response\RedirectResponse;

// JSON Response (RFC 8259 compliant)
$data = ['status' => 'success', 'count' => 42];
$response = new JsonResponse($data, 200);

// HTML Response
$response = new HtmlResponse('<h1>Hello World</h1>');

// Redirect (validates 3xx status codes)
$response = new RedirectResponse('/login', 302);

// Sending the response
$response->send();
```

## Security Notes

This library enforces several OWASP and NIST best practices:

*   **Standard 2.3.1 (Error Handling)**: Fail-fast behavior is implemented in getters. Invalid types (e.g., requesting an Integer from a text field "abc") throw `InvalidArgumentException` immediately.
*   **Standard 5.1.1 (PHP)**: All input is treated as untrusted. The wrappers canonicalize data (trim whitespace) before use.
*   **Standard 4.2.2 (Error Handling)**: `JsonResponse` uses `JSON_THROW_ON_ERROR` to ensure valid JSON output, preventing malformed responses.
*   **Immutability**: Readonly classes prevent accidental pollution of the request state during execution.

## Full Example (`index.php`)

A complete implementation combining all components:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Http\Request;
use App\Http\Response\JsonResponse;
use App\Http\Response\HtmlResponse;
use App\Http\Routing\RouteCollection;
use App\Http\Routing\Router;
use App\Http\Routing\UrlGenerator;

// 1. Configuration
$baseUrl = 'http://localhost:8080/index.php';

// 2. Define Routes
$routes = new RouteCollection();

$routes->get('/', function (Request $request) {
    return new HtmlResponse('<h1>Welcome</h1><p>Try <a href="?route=/user/42">User 42</a></p>');
});

$routes->get('/user/{id}', function (Request $request, array $params) {
    $id = $params['id'];
    return new JsonResponse(['user_id' => (int)$id, 'status' => 'active']);
});

// 3. Initialize Services
$router = new Router($routes);
$url = new UrlGenerator($baseUrl);

// 4. Process Request
$request = Request::fromGlobals();

// 5. Dispatch and Send
$response = $router->dispatch($request);
$response->send();
```

## License

MIT License. See `LICENSE` file for details.
