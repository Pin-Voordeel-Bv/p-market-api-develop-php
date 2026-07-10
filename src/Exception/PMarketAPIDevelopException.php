<?php
declare(strict_types=1);
namespace PinVandaag\PMarketAPIDevelop\Exception;
use RuntimeException;
final class PMarketAPIDevelopException extends RuntimeException
{
    public function __construct(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
        public readonly ?array $response = null,
    ) { parent::__construct($message, $code, $previous); }
}
