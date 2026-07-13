<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPIDevelop\Support;

use PinVandaag\PMarketAPIDevelop\Exception\PMarketAPIDevelopException;

final readonly class UploadedFile
{
    public function __construct(
        public string $bytesContent,
        public string $name,
        public string $originalFilename,
        public string $contentType,
    ) {
    }

    public static function fromPath(
        string $path,
        ?string $name = null,
        ?string $contentType = null,
    ): self {
        if (!is_file($path) || !is_readable($path)) {
            throw new PMarketAPIDevelopException(sprintf('File "%s" does not exist or is not readable.', $path));
        }

        $bytesContent = file_get_contents($path);
        if ($bytesContent === false) {
            throw new PMarketAPIDevelopException(sprintf('Could not read file "%s".', $path));
        }

        $originalFilename = basename($path);

        return new self(
            bytesContent: $bytesContent,
            name: $name ?? $originalFilename,
            originalFilename: $originalFilename,
            contentType: $contentType ?? (mime_content_type($path) ?: 'application/octet-stream'),
        );
    }
}
