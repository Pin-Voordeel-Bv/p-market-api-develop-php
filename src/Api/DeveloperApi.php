<?php

namespace PinVandaag\PMarketApiDevelop\Api;

final class DeveloperApi
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $apiSecret,
    ) {}

    public function createApp(array $payload): array
    {
        throw new \RuntimeException('Not implemented yet.');
    }

    public function getAppInfoByName(string $name): array
    {
        throw new \RuntimeException('Not implemented yet.');
    }

    public function uploadApk(array $payload): array
    {
        throw new \RuntimeException('Not implemented yet.');
    }

    public function createApk(array $payload): array
    {
        throw new \RuntimeException('Not implemented yet.');
    }

    public function editApk(array $payload): array
    {
        throw new \RuntimeException('Not implemented yet.');
    }
}
