<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Main Request object encapsulating all PHP superglobals.
 */
final readonly class Request
{
    /**
     * @param InputData $query Wrapper for $_GET
     * @param InputData $body Wrapper for $_POST
     * @param ServerData $server Wrapper for $_SERVER
     * @param InputData $cookies Wrapper for $_COOKIE
     * @param array<string, FileUpload> $files Processed $_FILES
     */
    public function __construct(public InputData $query, public InputData $body, public ServerData $server, public InputData $cookies, private array $files = []) {}

    /**
     * Factory method to create Request from PHP superglobals.
     */
    public static function fromGlobals(): self
    {
        return new self(
            query: new InputData($_GET),
            body: new InputData($_POST),
            server: new ServerData($_SERVER),
            cookies: new InputData($_COOKIE),
            files: self::processFiles($_FILES),
        );
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

    /**
     * Converts the complex $_FILES structure into an array of FileUpload objects.
     *
     * @param array<string, mixed> $rawFiles
     *
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
            if (is_array($data['name'])) {
                foreach ($data['name'] as $idx => $name) {
                    // Reconstruct flat array structure for each file
                    $processed[$key][$idx] = new FileUpload(
                        name: $name,
                        type: $data['type'][$idx],
                        size: $data['size'][$idx],
                        tmpName: $data['tmp_name'][$idx],
                        error: $data['error'][$idx],
                    );
                }
            }
        }

        return $processed;
    }
}
