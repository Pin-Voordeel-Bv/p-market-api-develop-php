<?php
declare(strict_types=1);
namespace PinVandaag\PMarketAPIDevelop\Model;
use PinVandaag\PMarketAPIDevelop\Exception\PMarketAPIDevelopException;
final readonly class UploadedFileContent
{
    public function __construct(
        public string $bytesContent,
        public string $name,
        public string $originalFilename,
        public string $contentType,
    ) {}
    public static function fromPath(string $path, ?string $name = null, ?string $contentType = null): self
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new PMarketAPIDevelopException(sprintf('File "%s" does not exist or is not readable.', $path));
        }
        $bytes = file_get_contents($path);
        if ($bytes === false) throw new PMarketAPIDevelopException(sprintf('Could not read file "%s".', $path));
        $filename = basename($path);
        return new self($bytes, $name ?? $filename, $filename, $contentType ?? (mime_content_type($path) ?: 'application/octet-stream'));
    }
    public function toArray(): array
    {
        return ['bytesContent'=>base64_encode($this->bytesContent),'name'=>$this->name,'originalFilename'=>$this->originalFilename,'contentType'=>$this->contentType];
    }
}
