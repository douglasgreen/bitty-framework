Here is a step-by-step plan to evolve the current toolkit into a robust microframework. The order is determined by architectural dependencies—infrastructure first, followed by application flow components, and finally feature modules.

### Phase 1: Infrastructure & Foundation

These components form the bedrock of the application. They are required by almost all other features.

#### Step 1: Dependency Injection (DI) Container
**Goal**: Manage class dependencies and service lifecycles.
*   **Description**: Currently, we manually instantiate `Router`, `Request`, etc. We need a central container to automate dependency injection, preventing manual `new` instantiation inside business logic.
*   **Key Components**:
    *   `Container`: Implements PSR-11 (`ContainerInterface`).
    *   `ServiceDefinition`: Defining how services are created (factories vs. autowiring).
*   **Standard Ref**: **MUST** inject dependencies via constructor; service location is prohibited.

#### Step 2: Configuration Management (TOML)
**Goal**: Externalize configuration and inject it into the container.
*   **Description**: Load configuration values from `.toml` files (e.g., database credentials, log levels) and make them available application-wide.
*   **Key Components**:
    *   `Config`: An immutable object wrapping parsed TOML data.
    *   `ConfigLoader`: Parses `.toml` files into the `Config` object.
*   **Standard Ref**: **MUST NOT** commit secrets to repositories; use environment variables or config files.

#### Step 3: Logging (PSR-3)
**Goal**: Establish observability before adding complex logic.
*   **Description**: Implement a logger that writes to files or streams. This is crucial for debugging the subsequent steps.
*   **Key Components**:
    *   `Logger`: Implements `Psr\Log\LoggerInterface`.
    *   `LogFormatter`: Standardizes log output (JSON structured logging preferred).
*   **Standard Ref**: **MUST** emit structured logs; **MUST NOT** contain secrets in logs.

---

### Phase 2: Application Flow

With the foundation laid, these steps control how the application processes requests.

#### Step 4: Middleware Pipeline
**Goal**: Filter and manipulate HTTP requests/responses globally.
*   **Description**: Refactor the `Router` to be the final "handler" in a middleware stack. This allows logic like "Check Auth -> Start Session -> Route -> Transform Response".
*   **Key Components**:
    *   `MiddlewareInterface`: `process(Request, Handler): Response`.
    *   `Pipeline`: Chains middleware execution.
    *   `Runner`: Executes the pipeline.
*   **Standard Ref**: Cross-cutting concerns **SHOULD** be implemented via middleware.

#### Step 5: Exception & Error Handling Middleware
**Goal**: Ensure the application never leaks stack traces or crashes silently.
*   **Description**: A dedicated middleware to catch all `Throwable` errors, log them, and convert them into safe, standardized error responses (RFC 7807).
*   **Key Components**:
    *   `ErrorHandlerMiddleware`: Wraps the entire application.
    *   `ErrorResponseGenerator`: Maps exceptions to specific HTTP status codes.
*   **Standard Ref**: **MUST NOT** expose stack traces or secrets in error responses.

#### Step 6: Event Manager
**Goal**: Decouple components via the Observer pattern.
*   **Description**: Allow parts of the system to react to events (e.g., "UserRegistered") without tight coupling.
*   **Key Components**:
    *   `EventManager`: Dispatches events to listeners.
    *   `ListenerProvider`: Registers listeners (often via the DI Container).
*   **Standard Ref**: **MUST** categorize events; **MUST** ensure published events are immutable.

---

### Phase 3: Features & Services

These components provide specific capabilities needed by the application logic.

#### Step 7: Caching (PSR-6 / PSR-16)
**Goal**: Improve performance by caching expensive operations.
*   **Description**: Integrate a caching layer, primarily for configuration caching, route caching, or database query results.
*   **Key Components**:
    *   `CacheItemPool`: Implements PSR-6.
    *   `FileCache` or `RedisCache`: Concrete storage implementations.
*   **Standard Ref**: **SHOULD** implement caching with explicit TTLs.

#### Step 8: HTML Templating
**Goal**: Render secure, accessible HTML views.
*   **Description**: A simple rendering engine that automatically escapes output to prevent XSS.
*   **Key Components**:
    *   `ViewRenderer`: Renders PHP templates.
    *   `Escaper`: Context-aware escaping (HTML, JS, Attributes).
*   **Standard Ref**: **MUST** escape HTML output; **MUST** use semantic HTML.

#### Step 9: Notifications
**Goal**: Send alerts (Email, Slack, etc.) to users or admins.
*   **Description**: An abstraction layer for sending messages. This allows swapping transports (e.g., SMTP to Mailgun) easily.
*   **Key Components**:
    *   `NotifierInterface`: `send(Notification): void`.
    *   `Notification`: A value object representing a message (recipient, subject, body).
*   **Standard Ref**: **MUST** handle retries and idempotency.

---

### Summary of Architecture Evolution

After completing these steps, the application entry point (`index.php`) will evolve from a procedural script into a clean, bootstrapped application:

```php
<?php
// Future index.php concept

declare(strict_types=1);

// 1. Load Config (TOML)
$config = ConfigLoader::load(__DIR__ . '/config.toml');

// 2. Build Container (DI)
$container = new Container($config);
$container->set(LoggerInterface::class, new FileLogger($config->get('log.path')));
$container->set(CacheInterface::class, new RedisCache($config->get('cache.dsn')));

// 3. Setup Middleware Pipeline
$pipeline = new Pipeline();
$pipeline->add(ErrorHandlerMiddleware::class); // Step 5
$pipeline->add(SessionMiddleware::class);      // Example Middleware
$pipeline->add(RouterMiddleware::class);       // Step 4 (Router refactored)

// 4. Run
$request = Request::fromGlobals();
$runner = new Runner($pipeline, $container);
$response = $runner->handle($request);
$response->send();
```
