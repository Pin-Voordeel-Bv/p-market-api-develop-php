<?php

namespace PinVandaag\PMarketApiDevelop;

use PinVandaag\PMarketApiDevelop\Api\DeveloperApi;

final class PMarketDevelopClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $apiSecret,
    ) {}

    public function developer(): DeveloperApi
    {
        return new DeveloperApi(
            $this->baseUrl,
            $this->apiKey,
            $this->apiSecret,
        );
    }
}
