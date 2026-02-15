<?php

declare(strict_types=1);

namespace App\Http\Response;

/**
 * Standard 3.1.1 (error-handling.md): Context-aware encoding (HTML).
 * Note: Actual escaping of the body content must happen before instantiation 
 * (typically in the View layer), but this class sets the correct Content-Type.
 */
final class HtmlResponse extends Response
{
    /**
     * @param string $html The HTML content (must be already escaped if user-generated)
     * @param int $status HTTP status code
     */
    public function __construct(string $html, int $status = 200)
    {
        parent::__construct(
            body: $html,
            status: $status,
            headers: ['Content-Type' => 'text/html; charset=utf-8']
        );
    }
}
