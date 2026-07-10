<?php
declare(strict_types=1);
namespace PinVandaag\PMarketAPIDevelop\Model;
final readonly class ApkOfflineRequest
{
    public function __construct(
        public string $comment,
    ) {}
}
