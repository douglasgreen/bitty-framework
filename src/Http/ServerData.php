<?php

declare(strict_types=1);

namespace App\Http;

use function filter_var;
use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;

/**
 * Immutable wrapper for $_SERVER superglobal.
 * Standard 1.1.1 (php.md): Separates infrastructure concerns from domain logic.
 */
final readonly class ServerData
{
    /**
     * @param array<string, mixed> $serverData Typically $_SERVER.
     */
    public function __construct(private array $serverData) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->serverData[$key] ?? $default;
    }

    public function getMethod(): string
    {
        return strtoupper($this->serverData['REQUEST_METHOD'] ?? 'GET');
    }

    public function getRequestUri(): string
    {
        return $this->serverData['REQUEST_URI'] ?? '/';
    }

    public function getScriptName(): string
    {
        return $this->serverData['SCRIPT_NAME'] ?? '';
    }

    public function getHost(): string
    {
        return $this->serverData['HTTP_HOST'] ?? $this->serverData['SERVER_NAME'] ?? '';
    }

    public function isHttps(): bool
    {
        $https = $this->serverData['HTTPS'] ?? '';
        return (!empty($https) && strtolower($https) !== 'off')
            || ($this->serverData['SERVER_PORT'] ?? 0) === 443;
    }

    /**
     * Retrieves the client IP address.
     * Standard 14.1.1 (architecture.md): MUST validate all external inputs.
     * Note: Proxies may set HTTP_CLIENT_IP or HTTP_X_FORWARDED_FOR.
     *       Validation is crucial to prevent injection.
     */
    public function getClientIp(): string
    {
        $ip = $this->serverData['REMOTE_ADDR'] ?? '127.0.0.1';

        // Validate IP format strictly to prevent injection attacks
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            return $ip;
        }

        return '0.0.0.0'; // Fallback for invalid IP
    }

    /**
     * Retrieves an HTTP header value.
     * Headers in $_SERVER are prefixed with 'HTTP_' and uppercase.
     */
    public function getHeader(string $name, string $default = ''): string
    {
        // Normalize header name to $_SERVER key format
        // e.g., "Content-Type" -> "HTTP_CONTENT_TYPE"
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        
        // Handle Content-Type and Content-Length which might not have HTTP_ prefix in some SAPIs
        if ($name === 'Content-Type') {
             $key = 'CONTENT_TYPE';
        } elseif ($name === 'Content-Length') {
             $key = 'CONTENT_LENGTH';
        }

        return $this->serverData[$key] ?? $default;
    }

    /**
     * Checks if the request is an AJAX request.
     */
    public function isAjax(): bool
    {
        return strtolower($this->getHeader('X-Requested-With')) === 'xmlhttprequest';
    }

    /**
     * Returns the raw server array.
     */
    public function all(): array
    {
        return $this->serverData;
    }
}
