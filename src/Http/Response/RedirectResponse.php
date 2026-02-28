<?php

declare(strict_types=1);

namespace App\Http\Response;

use Override;
use InvalidArgumentException;

/**
 * Use correct HTTP methods/semantics.
 * Redirects strictly use 3xx status codes.
 */
final class RedirectResponse extends Response
{
    /**
     * @param string $url The URL to redirect to
     * @param int $status HTTP status code (301, 302, 303, 307, 308)
     */
    public function __construct(string $url, int $status = 302)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, '/')) {
            throw new InvalidArgumentException('Invalid redirect URL provided.');
        }

        parent::__construct(
            body: '',
            headers: ['Location' => $url],
            // Redirects typically have empty bodies
            status: $status,
        );
    }

    /**
     * Ensures only 3xx codes are used for redirection.
     */
    #[Override]
    protected function validateStatusCode(int $code): void
    {
        parent::validateStatusCode($code);

        if ($code < 300 || $code >= 400) {
            throw new InvalidArgumentException(
                sprintf('Redirect response requires a 3xx status code, %d given.', $code),
            );
        }
    }
}
