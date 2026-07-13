<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPIDevelop\Client\Traits;

use PinVandaag\PMarketAPIDevelop\Exception\PMarketAPIDevelopException;
use PinVandaag\PMarketAPIDevelop\Model\ApkInfo;
use PinVandaag\PMarketAPIDevelop\Model\ApkOfflineRequest;
use PinVandaag\PMarketAPIDevelop\Model\ApkVersion;
use PinVandaag\PMarketAPIDevelop\Model\AppDetail;
use PinVandaag\PMarketAPIDevelop\Model\AppKeySecret;
use PinVandaag\PMarketAPIDevelop\Model\CodeInfo;
use PinVandaag\PMarketAPIDevelop\Model\CreateApkRequest;
use PinVandaag\PMarketAPIDevelop\Model\CreateMultipleApkRequest;
use PinVandaag\PMarketAPIDevelop\Model\CreateSingleApkRequest;
use PinVandaag\PMarketAPIDevelop\Model\CreateSingleAppRequest;
use PinVandaag\PMarketAPIDevelop\Model\EditAppKeySecretRequest;
use PinVandaag\PMarketAPIDevelop\Model\EditSingleApkRequest;
use PinVandaag\PMarketAPIDevelop\Model\PageResult;
use PinVandaag\PMarketAPIDevelop\Model\UploadedFileContent;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;

trait DeveloperApiTrait
{
    public function uploadApk(CreateApkRequest $request): int|string
    {
        $this->validateUploadApkRequest($request);

        $result = $this->multipartResultRawData(
            endpoint: '/v1/3rd/developer/apk/upload',
            actionDescription: 'upload P Market Developer APK',
            multipart: $this->apkMultipart($request, includeAppFile: true),
        );

        return $this->assertResultIdentifier($result, 'APK id');
    }

    public function createApp(CreateSingleAppRequest $request): int|string
    {
        $appName = $this->assertNonEmptyString($request->appName, 'appName');
        if (mb_strlen($appName) > 64) {
            throw new PMarketAPIDevelopException('appName cannot contain more than 64 characters.');
        }

        if ($request->appKey !== null && !preg_match('/^[A-Za-z0-9]{20}$/', $request->appKey)) {
            throw new PMarketAPIDevelopException('appKey must be exactly 20 letters and numbers.');
        }

        $result = $this->resultRawData(
            method: 'POST',
            endpoint: '/v1/3rd/developer/apps',
            actionDescription: sprintf('create P Market Developer app "%s"', $appName),
            body: $request,
        );

        return $this->assertResultIdentifier($result, 'app id');
    }

    public function getAppInfoByName(?string $packageName = null, ?string $appName = null): AppDetail
    {
        $packageName = $packageName !== null ? trim($packageName) : null;
        $appName = $appName !== null ? trim($appName) : null;

        if (($packageName === null || $packageName === '') && ($appName === null || $appName === '')) {
            throw new PMarketAPIDevelopException('packageName and appName cannot be null at the same time.');
        }

        /** @var AppDetail */
        return $this->resultData(
            method: 'GET',
            endpoint: '/v1/3rd/developer/apps',
            responseClass: AppDetail::class,
            actionDescription: 'get P Market Developer app information by name',
            query: array_filter(
                ['packageName' => $packageName, 'appName' => $appName],
                static fn (?string $value): bool => $value !== null && $value !== '',
            ),
        );
    }

    public function createApk(CreateSingleApkRequest $request): int|string
    {
        $appId = $this->assertPositiveInteger($request->appId, 'appId');
        $this->validateSingleApkRequest($request);

        $result = $this->multipartResultRawData(
            endpoint: sprintf('/v1/3rd/developer/apps/%s/apks', rawurlencode((string) $appId)),
            actionDescription: sprintf('create P Market Developer APK for app "%s"', $appId),
            multipart: $this->apkMultipart($request, includeAppFile: true),
        );

        return $this->assertResultIdentifier($result, 'APK id');
    }

    public function createMultipleApk(CreateMultipleApkRequest $request): int|string
    {
        $appId = $this->assertPositiveInteger($request->appId, 'appId');
        $this->validateMultipleApkRequest($request);

        $result = $this->multipartResultRawData(
            endpoint: sprintf('/v1/3rd/developer/apps/%s/multiple/apks', rawurlencode((string) $appId)),
            actionDescription: sprintf('create multiple P Market Developer APKs for app "%s"', $appId),
            multipart: $this->apkMultipart($request, multipleAppFiles: true),
        );

        return $this->assertResultIdentifier($result, 'APK id');
    }

    public function editApk(EditSingleApkRequest $request): bool
    {
        $apkId = $this->assertPositiveInteger($request->apkId, 'apkId');
        $this->validateEditApkRequest($request);

        $this->emptyMultipartResult(
            endpoint: sprintf('/v1/3rd/developer/apks/%s', rawurlencode((string) $apkId)),
            actionDescription: sprintf('edit P Market Developer APK "%s"', $apkId),
            multipart: $this->apkMultipart($request, includeAppFile: $request->appFile !== null),
        );

        return true;
    }

    public function submitApk(int|string $apkId): bool
    {
        $apkId = $this->assertPositiveInteger($apkId, 'apkId');

        $this->emptyResult(
            method: 'POST',
            endpoint: sprintf('/v1/3rd/developer/apks/%s/submit', rawurlencode((string) $apkId)),
            actionDescription: sprintf('submit P Market Developer APK "%s"', $apkId),
        );

        return true;
    }

    public function deleteApk(int|string $apkId): bool
    {
        $apkId = $this->assertPositiveInteger($apkId, 'apkId');

        $this->emptyResult(
            method: 'DELETE',
            endpoint: sprintf('/v1/3rd/developer/apks/%s', rawurlencode((string) $apkId)),
            actionDescription: sprintf('delete P Market Developer APK "%s"', $apkId),
        );

        return true;
    }

    public function deleteApp(int|string $appId): bool
    {
        $appId = $this->assertPositiveInteger($appId, 'appId');

        $this->emptyResult(
            method: 'DELETE',
            endpoint: sprintf('/v1/3rd/developer/apps/%s', rawurlencode((string) $appId)),
            actionDescription: sprintf('delete P Market Developer app "%s"', $appId),
        );

        return true;
    }

    public function getAppCategory(string $language = 'en'): PageResult
    {
        $language = $this->assertNonEmptyString($language, 'language');

        $response = $this->request(
            method: 'GET',
            endpoint: '/v1/3rd/developer/codes',
            query: ['codeType' => 'app_category', 'lang' => $language],
            options: ['headers' => $this->defaultHeaders()],
            actionDescription: 'get P Market Developer app categories',
        );

        return $this->deserializePageResult($response, CodeInfo::class, 'get P Market Developer app categories');
    }

    public function getApkById(int|string $apkId): ApkInfo
    {
        $apkId = $this->assertPositiveInteger($apkId, 'apkId');

        /** @var ApkInfo */
        return $this->resultData(
            method: 'GET',
            endpoint: sprintf('/v1/3rd/developer/apks/%s', rawurlencode((string) $apkId)),
            responseClass: ApkInfo::class,
            actionDescription: sprintf('get P Market Developer APK "%s"', $apkId),
        );
    }

    public function getAppKeySecret(int|string $appId): AppKeySecret
    {
        $appId = $this->assertPositiveInteger($appId, 'appId');

        /** @var AppKeySecret */
        return $this->resultData(
            method: 'GET',
            endpoint: sprintf('/v1/3rd/developer/apps/%s/key-secret', rawurlencode((string) $appId)),
            responseClass: AppKeySecret::class,
            actionDescription: sprintf('get key and secret for P Market Developer app "%s"', $appId),
        );
    }

    public function updateAppKeySecret(int|string $appId, EditAppKeySecretRequest $request): bool
    {
        $appId = $this->assertPositiveInteger($appId, 'appId');
        $this->assertNonEmptyString($request->appKey, 'appKey');
        $this->assertNonEmptyString($request->appSecret, 'appSecret');

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rd/developer/apps/%s/key-secret', rawurlencode((string) $appId)),
            actionDescription: sprintf('update key and secret for P Market Developer app "%s"', $appId),
            body: $request,
        );

        return true;
    }

    public function getApkVersionList(int|string $appId): PageResult
    {
        $appId = $this->assertPositiveInteger($appId, 'appId');

        $response = $this->request(
            method: 'GET',
            endpoint: sprintf('/v1/3rd/developer/%s/apks/version-list', rawurlencode((string) $appId)),
            query: [],
            options: ['headers' => $this->defaultHeaders()],
            actionDescription: sprintf('get APK version list for P Market Developer app "%s"', $appId),
        );

        return $this->deserializePageResult(
            $response,
            ApkVersion::class,
            sprintf('get APK version list for P Market Developer app "%s"', $appId),
        );
    }

    public function offlineApkById(int|string $apkId, ApkOfflineRequest $request): bool
    {
        $apkId = $this->assertPositiveInteger($apkId, 'apkId');
        $this->assertNonEmptyString($request->comment, 'comment');

        $this->emptyResult(
            method: 'POST',
            endpoint: sprintf('/v1/3rd/developer/apks/%s/offline', rawurlencode((string) $apkId)),
            actionDescription: sprintf('take P Market Developer APK "%s" offline', $apkId),
            body: $request,
        );

        return true;
    }

    private function validateUploadApkRequest(CreateApkRequest $request): void
    {
        $this->assertNonEmptyString($request->appName, 'appName');
        $this->assertApkType($request->baseType, 'baseType');

        if (!in_array($request->chargeType, [0, 1], true)) {
            throw new PMarketAPIDevelopException('chargeType must be 0 or 1.');
        }

        $this->validateApkCommon(
            apkType: $request->baseType,
            modelNameList: $request->modelNameList,
            categoryList: $request->categoryList,
            shortDesc: $request->shortDesc,
            description: $request->description,
            screenshotFileList: $request->screenshotFileList,
            paramTemplateFileList: $request->paramTemplateFileList,
        );
    }

    private function validateSingleApkRequest(CreateSingleApkRequest $request): void
    {
        $this->assertNonEmptyString($request->apkName, 'apkName');
        $this->validateApkCommon(
            apkType: $request->apkType,
            modelNameList: $request->modelNameList,
            categoryList: $request->categoryList,
            shortDesc: $request->shortDesc,
            description: $request->description,
            screenshotFileList: $request->screenshotFileList,
            paramTemplateFileList: $request->paramTemplateFileList,
        );
    }

    private function validateMultipleApkRequest(CreateMultipleApkRequest $request): void
    {
        $this->assertNonEmptyString($request->apkName, 'apkName');
        $this->validateApkCommon(
            apkType: $request->apkType,
            modelNameList: $request->modelNameList,
            categoryList: $request->categoryList,
            shortDesc: $request->shortDesc,
            description: $request->description,
            screenshotFileList: $request->screenshotFileList,
            paramTemplateFileList: $request->paramTemplateFileList,
        );

        if ($request->multipleAppFile === []) {
            throw new PMarketAPIDevelopException('multipleAppFile cannot be empty.');
        }

        foreach ($request->multipleAppFile as $factory => $file) {
            if (!is_string($factory) || trim($factory) === '' || !$file instanceof UploadedFileContent) {
                throw new PMarketAPIDevelopException('multipleAppFile must map a non-empty manufacturer name to UploadedFileContent.');
            }
        }
    }

    private function validateEditApkRequest(EditSingleApkRequest $request): void
    {
        $this->assertNonEmptyString($request->apkName, 'apkName');
        $this->validateApkCommon(
            apkType: $request->apkType,
            modelNameList: $request->modelNameList,
            categoryList: $request->categoryList,
            shortDesc: $request->shortDesc,
            description: $request->description,
            screenshotFileList: $request->screenshotFileList,
            paramTemplateFileList: $request->paramTemplateFileList,
            screenshotMinimum: 0,
        );
    }

    /**
     * @param list<string> $modelNameList
     * @param list<string> $categoryList
     * @param list<UploadedFileContent> $screenshotFileList
     * @param list<UploadedFileContent> $paramTemplateFileList
     */
    private function validateApkCommon(
        string $apkType,
        array $modelNameList,
        array $categoryList,
        string $shortDesc,
        string $description,
        array $screenshotFileList,
        array $paramTemplateFileList,
        int $screenshotMinimum = 3,
    ): void {
        $this->assertApkType($apkType, 'apkType');
        $this->assertStringList($modelNameList, 'modelNameList');
        $this->assertStringList($categoryList, 'categoryList');
        $this->assertNonEmptyString($shortDesc, 'shortDesc');
        $this->assertNonEmptyString($description, 'description');
        $this->assertUploadedFiles($screenshotFileList, 'screenshotFileList', $screenshotMinimum);
        $this->assertUploadedFiles($paramTemplateFileList, 'paramTemplateFileList');

        if ($apkType === 'P' && $paramTemplateFileList === []) {
            throw new PMarketAPIDevelopException('paramTemplateFileList is mandatory when apkType is P.');
        }
    }

    private function assertApkType(string $apkType, string $fieldName): void
    {
        if (!in_array($apkType, ['P', 'N'], true)) {
            throw new PMarketAPIDevelopException(sprintf('%s must be P or N.', $fieldName));
        }
    }

    /** @param list<string> $values */
    private function assertStringList(array $values, string $fieldName): void
    {
        if ($values === []) {
            throw new PMarketAPIDevelopException(sprintf('%s cannot be empty.', $fieldName));
        }

        foreach ($values as $value) {
            if (!is_string($value) || trim($value) === '') {
                throw new PMarketAPIDevelopException(sprintf('%s must contain non-empty strings.', $fieldName));
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function apkMultipart(
        object $request,
        bool $includeAppFile = false,
        bool $multipleAppFiles = false,
    ): array {
        $multipart = [];

        if ($includeAppFile && property_exists($request, 'appFile') && $request->appFile instanceof UploadedFileContent) {
            $multipart[] = $this->multipartFile('apkFile', $request->appFile);
        }

        foreach (['iconFile' => 'iconFile', 'featuredImgFile' => 'featuredImg', 'attachment' => 'attachment'] as $property => $field) {
            if (property_exists($request, $property) && $request->{$property} instanceof UploadedFileContent) {
                $multipart[] = $this->multipartFile($field, $request->{$property});
            }
        }

        foreach ($request->screenshotFileList as $index => $file) {
            $multipart[] = $this->multipartFile('screenshot#' . $index, $file);
        }

        $paramTemplateNames = [];
        foreach ($request->paramTemplateFileList as $file) {
            $multipart[] = $this->multipartFile('paramTemplate#' . $file->name, $file);
            $paramTemplateNames[] = $file->name;
        }

        if ($multipleAppFiles && property_exists($request, 'multipleAppFile')) {
            foreach ($request->multipleAppFile as $factory => $file) {
                $multipart[] = $this->multipartFile('factory#' . $factory, $file);
            }
        }

        $multipart[] = [
            'name' => 'apkDetail',
            'contents' => $this->serialize(
                $this->apkDetailPayload($request, $paramTemplateNames),
                'serialize P Market Developer APK detail',
            ),
        ];

        return $multipart;
    }

    /**
     * @param list<string> $paramTemplateNames
     * @return array<string, mixed>
     */
    private function apkDetailPayload(object $request, array $paramTemplateNames): array
    {
        try {
            $payload = $this->serializer->normalize($request);
        } catch (SerializerException $exception) {
            throw new PMarketAPIDevelopException('Could not normalize P Market Developer APK request.', 0, $exception);
        }

        if (!is_array($payload)) {
            throw new PMarketAPIDevelopException('Could not normalize P Market Developer APK request.');
        }

        foreach ([
            'appFile', 'iconFile', 'featuredImgFile', 'attachment', 'screenshotFileList',
            'paramTemplateFileList', 'multipleAppFile', 'appId', 'apkId',
        ] as $field) {
            unset($payload[$field]);
        }

        if ($paramTemplateNames !== []) {
            $payload['paramTemplateList'] = $paramTemplateNames;
        }

        return array_filter($payload, static fn (mixed $value): bool => $value !== null);
    }

    private function assertResultIdentifier(mixed $value, string $fieldName): int|string
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value) && (int) $value > 0) {
            return $value;
        }

        throw new PMarketAPIDevelopException(sprintf('P Market Developer API returned an invalid %s.', $fieldName));
    }

    /**
     * @template T of object
     * @param class-string<T> $responseClass
     */
    private function deserializePageResult(
        ResponseInterface $response,
        string $responseClass,
        string $actionDescription,
    ): PageResult {
        $decoded = $this->decodeSuccessfulResult($response, $actionDescription);
        $pageInfo = $decoded['pageInfo'] ?? $decoded;
        $dataSet = $pageInfo['dataSet'] ?? $pageInfo['dataset'] ?? [];

        $items = [];
        foreach (is_array($dataSet) ? $dataSet : [] as $itemData) {
            if (!is_array($itemData)) {
                continue;
            }

            try {
                $items[] = $this->serializer->denormalize($itemData, $responseClass);
            } catch (SerializerException $exception) {
                throw new PMarketAPIDevelopException(
                    sprintf('Could not deserialize P Market Developer API response for %s.', $actionDescription),
                    0,
                    $exception,
                );
            }
        }

        return new PageResult(
            pageNo: (int) ($pageInfo['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? count($items)),
            totalCount: (int) ($pageInfo['totalCount'] ?? count($items)),
            hasNext: (bool) ($pageInfo['hasNext'] ?? false),
            dataSet: $items,
        );
    }
}
