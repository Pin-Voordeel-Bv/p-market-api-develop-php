<?php
declare(strict_types=1);
namespace PinVandaag\PMarketAPIDevelop\Model;
final readonly class CreateSingleAppRequest
{
    public function __construct(
        public string $appName,
        public ?string $appKey = null,
    ) {}
}
