# p-market-api-develop

PHP 8.2 SDK for the PAXSTORE Developer API.

```bash
composer require pinvandaag/p-market-api-develop
```

```php
use PinVandaag\PMarketAPIDevelop\PMarketAPIDevelopClient;
use PinVandaag\PMarketAPIDevelop\Model\CreateSingleAppRequest;

$client = (new PMarketAPIDevelopClient())->configure(
    baseUri: 'https://api.whatspos.com/p-market-api',
    apiKey: $_ENV['P_MARKET_DEVELOP_API_KEY'],
    apiSecret: $_ENV['P_MARKET_DEVELOP_API_SECRET'],
);

$appId = $client->createApp(new CreateSingleAppRequest('Pin Vandaag'));
```

Implemented:
- uploadApk
- createApp
- getAppInfoByName
- createApk
- createMultipleApk
- editApk
- submitApk
- deleteApk
- deleteApp
- getAppCategory
- getApkById
- getAppKeySecret
- updateAppKeySecret
- getApkVersionList
- offlineApkById

Files are represented by `UploadedFileContent::fromPath()`. Symfony Serializer is injectable through the constructor.
