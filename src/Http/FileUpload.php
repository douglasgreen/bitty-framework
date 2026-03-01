<?php

declare(strict_types=1);

namespace App\DouglasGreen\BittyFramework\Http;

use RuntimeException;

use function is_uploaded_file;
use function move_uploaded_file;

/**
 * Value object for a single uploaded file.
 */
final readonly class FileUpload
{
    /**
     * @param string $name Original name of the file.
     * @param string $type Mime type provided by the browser (untrusted).
     * @param int $size File size in bytes.
     * @param string $tmpName Temporary filename on server.
     * @param int $error Upload error code (UPLOAD_ERR_*).
     */
    public function __construct(
        public string $name,
        public string $type,
        public int $size,
        public string $tmpName,
        public int $error,
    ) {}

    /**
     * Factory method to create from $_FILES array structure.
     *
     * @param array<string, mixed> $fileData
     */
    public static function fromArray(array $fileData): self
    {
        $keys = ['name', 'type', 'size', 'tmp_name', 'error'];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $fileData)) {
                throw new RuntimeException(sprintf('Missing key "%s" in file upload data', $key));
            }
        }

        return new self(
            name: (string) $fileData['name'],
            type: (string) $fileData['type'],
            size: (int) $fileData['size'],
            tmpName: (string) $fileData['tmp_name'],
            error: (int) $fileData['error'],
        );
    }

    /**
     * Checks if the file was uploaded successfully.
     */
    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    /**
     * Returns the upload error message if not successful.
     */
    public function getErrorMessage(): string
    {
        return match ($this->error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File size exceeds limit.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.',
            UPLOAD_ERR_OK => '',
            default => 'Unknown upload error.',
        };
    }

    /**
     * Moves the uploaded file to a new location.
     * Uses move_uploaded_file() for security.
     */
    public function moveTo(string $targetPath): bool
    {
        if (!$this->isValid()) {
            throw new RuntimeException('Cannot move invalid file upload: ' . $this->getErrorMessage());
        }

        // Security check: ensure the file is a legitimate upload
        if (!is_uploaded_file($this->tmpName)) {
            throw new RuntimeException('Security alert: Attempted move of non-uploaded file.');
        }

        return move_uploaded_file($this->tmpName, $targetPath);
    }

    /**
     * Gets the file extension from the original name.
     * Note: This is client-provided data and should not be trusted for security decisions.
     */
    public function getClientExtension(): string
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }
}
