<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Main Request object encapsulating all PHP superglobals.
 * Standard 1.1.1 (php.md): Separates domain logic from global state.
 * Standard 1.1.3 (php.md): Dependencies injected via constructor.
 */
final readonly class Request
{
    public InputData $query;   // $_GET
    public InputData $body;    // $_POST
    public ServerData $server; // $_SERVER
    public InputData $cookies; // $_COOKIE

    /**
     * @var array<string, FileUpload>
     */
    private array $files;

    /**
     * @param InputData $query Wrapper for $_GET
     * @param InputData $body Wrapper for $_POST
     * @param ServerData $server Wrapper for $_SERVER
     * @param InputData $cookies Wrapper for $_COOKIE
     * @param array<string, FileUpload> $files Processed $_FILES
     */
    public function __construct(
        InputData $query,
        InputData $body,
        ServerData $server,
        InputData $cookies,
        array $files = []
    ) {
        $this->query = $query;
        $this->body = $body;
        $this->server = $server;
        $this->cookies = $cookies;
        $this->files = $files;
    }

    /**
     * Factory method to create Request from PHP superglobals.
     * Standard 1.1.2 (php.md): Encapsulates global state access.
     * 
     * @return self
     */
    public static function fromGlobals(): self
    {
        // Standard 2.1.1 (error-handling.md): Canonicalization happens inside InputData.
        return new self(
            query: new InputData($_GET),
            body: new InputData($_POST),
            server: new ServerData($_SERVER),
            cookies: new InputData($_COOKIE),
            files: self::processFiles($_FILES)
        );
    }

    /**
     * Converts the complex $_FILES structure into an array of FileUpload objects.
     * 
     * @param array<string, mixed> $rawFiles
     * @return array<string, FileUpload>
     */
    private static function processFiles(array $rawFiles): array
    {
        $processed = [];
        
        foreach ($rawFiles as $key => $data) {
            // Handle single file upload
            if (isset($data['name']) && !is_array($data['name'])) {
                $processed[$key] = FileUpload::fromArray($data);
                continue;
            }

            // Handle multi-file upload (e.g., name="files[]")
            // This implementation simplifies complex nested arrays. 
            // Standard 1.2.1 (error-handling.md): Fail-fast on invalid structure.
            if (is_array($data['name'])) {
                foreach ($data['name'] as $idx => $name) {
                     // Reconstruct flat array structure for each file
                     $processed[$key][$idx] = new FileUpload(
                        name: $name,
                        type: $data['type'][$idx],
                        size: $data['size'][$idx],
                        tmpName: $data['tmp_name'][$idx],
                        error: $data['error'][$idx]
                     );
                }
            }
        }

        return $processed;
    }

    /**
     * Retrieves a file upload by key.
     */
    public function getFile(string $key): ?FileUpload
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Retrieves all file uploads.
     * 
     * @return array<string, FileUpload>
     */
    public function getFiles(): array
    {
        return $this->files;
    }
}
