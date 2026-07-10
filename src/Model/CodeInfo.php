<?php
declare(strict_types=1);
namespace PinVandaag\PMarketAPIDevelop\Model;
final readonly class CodeInfo
{
    public function __construct(
        public string $value,
        public string $label,
    ) {}
}
