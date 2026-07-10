<?php
declare(strict_types=1);
namespace PinVandaag\PMarketAPIDevelop\Model;
final readonly class ApkVersion
{
    public function __construct(
        public int|string $apkId,
        public string $status,
        public int|string $versionCode,
        public string $versionName,
    ) {}
}
