# p-market-api-develop-php

A PHP wrapper for the <a href="https://github.com/PAXSTORE/paxstore-develop-sdk/blob/master/docs/DEVELOPER_API.md">PAXSTORE Developer API</a>.

## Installation

```bash
composer require pinvandaag/p-market-api-develop-php
```

## Configuration

```php
use PinVandaag\PMarketAPIDevelop\PMarketAPIDevelopClient;

$client = (new PMarketAPIDevelopClient())->configure(
    baseUri: 'https://api.whatspos.com/p-market-api',
    apiKey: $_ENV['P_MARKET_DEVELOP_API_KEY'],
    apiSecret: $_ENV['P_MARKET_DEVELOP_API_SECRET'],
    timeZone: 'Europe/Amsterdam',
    contentLanguage: 'en',
);
```

## Structure

- `PMarketAPIDevelopClient`: public facade, matching the original package.
- `Client\APIClient`: authentication, HTTP requests, Symfony Serializer, exceptions, validation and multipart helpers.
- `Client\Traits\DeveloperApiTrait`: all Developer API endpoint implementations.
- `Model`: request and response DTOs.

Authentication follows the official PAX Developer Java SDK: `devKey` and millisecond `timestamp` are appended to the query string, and the uppercase MD5 of `queryString + apiSecret` is sent in the `signature` header.

`updateAppKeySecret()` expects `appKey` and `appSecret` already encrypted with the Java SDK-compatible AES/SHA1PRNG implementation.
