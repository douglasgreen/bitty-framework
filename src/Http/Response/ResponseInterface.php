<?php

declare(strict_types=1);

namespace App\Http\Response;

/**
 * Defines inputs, outputs, and contracts.
 */
interface ResponseInterface
{
    /**
     * Retrieves the HTTP status code.
     */
    public function getStatusCode(): int;

    /**
     * Retrieves all response headers.
     *
     * @return array<string, string|string[]>
     */
    public function getHeaders(): array;

    /**
     * Retrieves the response body as a string.
     */
    public function getBody(): string;

    /**
     * Sends the response to the client.
     * This method handles headers and body output.
     */
    public function send(): void;
}
