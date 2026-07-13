<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPIDevelop;

use GuzzleHttp\Client;
use PinVandaag\PMarketAPIDevelop\Client\APIClient;
use PinVandaag\PMarketAPIDevelop\Model\ApkInfo;
use PinVandaag\PMarketAPIDevelop\Model\ApkOfflineRequest;
use PinVandaag\PMarketAPIDevelop\Model\AppDetail;
use PinVandaag\PMarketAPIDevelop\Model\AppKeySecret;
use PinVandaag\PMarketAPIDevelop\Model\CreateApkRequest;
use PinVandaag\PMarketAPIDevelop\Model\CreateMultipleApkRequest;
use PinVandaag\PMarketAPIDevelop\Model\CreateSingleApkRequest;
use PinVandaag\PMarketAPIDevelop\Model\CreateSingleAppRequest;
use PinVandaag\PMarketAPIDevelop\Model\EditAppKeySecretRequest;
use PinVandaag\PMarketAPIDevelop\Model\EditSingleApkRequest;
use PinVandaag\PMarketAPIDevelop\Model\PageResult;
use Psr\Log\LoggerInterface;
use SensitiveParameter;

final class PMarketAPIDevelopClient
{
    private APIClient $apiClient;

    public function __construct(
        ?APIClient $apiClient = null,
        ?LoggerInterface $logger = null,
        ?string $baseUri = null,
    ) {
        $this->apiClient = $apiClient ?? new APIClient(new Client(), $baseUri ?? '');

        if ($logger !== null) {
            $this->apiClient->setLogger($logger);
        }
    }

    public function configure(
        string $baseUri,
        #[SensitiveParameter] string $apiKey,
        #[SensitiveParameter] string $apiSecret,
        string $timeZone = 'UTC',
        string $contentLanguage = 'en',
    ): self {
        $this->apiClient
            ->setBaseUri($baseUri)
            ->setApiKey($apiKey)
            ->setApiSecret($apiSecret)
            ->setTimeZone($timeZone)
            ->setContentLanguage($contentLanguage);

        return $this;
    }

    public function uploadApk(CreateApkRequest $request): int|string { return $this->apiClient->uploadApk($request); }
    public function createApp(CreateSingleAppRequest $request): int|string { return $this->apiClient->createApp($request); }
    public function getAppInfoByName(?string $packageName = null, ?string $appName = null): AppDetail { return $this->apiClient->getAppInfoByName($packageName, $appName); }
    public function createApk(CreateSingleApkRequest $request): int|string { return $this->apiClient->createApk($request); }
    public function createMultipleApk(CreateMultipleApkRequest $request): int|string { return $this->apiClient->createMultipleApk($request); }
    public function editApk(EditSingleApkRequest $request): bool { return $this->apiClient->editApk($request); }
    public function submitApk(int|string $apkId): bool { return $this->apiClient->submitApk($apkId); }
    public function deleteApk(int|string $apkId): bool { return $this->apiClient->deleteApk($apkId); }
    public function deleteApp(int|string $appId): bool { return $this->apiClient->deleteApp($appId); }
    public function getAppCategory(string $language = 'en'): PageResult { return $this->apiClient->getAppCategory($language); }
    public function getApkById(int|string $apkId): ApkInfo { return $this->apiClient->getApkById($apkId); }
    public function getAppKeySecret(int|string $appId): AppKeySecret { return $this->apiClient->getAppKeySecret($appId); }
    public function updateAppKeySecret(int|string $appId, EditAppKeySecretRequest $request): bool { return $this->apiClient->updateAppKeySecret($appId, $request); }
    public function getApkVersionList(int|string $appId): PageResult { return $this->apiClient->getApkVersionList($appId); }
    public function offlineApkById(int|string $apkId, ApkOfflineRequest $request): bool { return $this->apiClient->offlineApkById($apkId, $request); }
}
