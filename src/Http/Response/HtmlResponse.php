<?php

declare(strict_types=1);

namespace DouglasGreen\BittyFramework\Http\Response;

/**
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
            headers: ['Content-Type' => 'text/html; charset=utf-8'],
            status: $status,
        );
    }
}
