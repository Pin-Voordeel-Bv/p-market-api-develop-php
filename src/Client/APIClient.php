<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPIDevelop\Client;

use GuzzleHttp\ClientInterface;
use PinVandaag\PMarketAPIDevelop\Client\Traits\DeveloperApiTrait;
use PinVandaag\PMarketAPIDevelop\Exception\PMarketAPIDevelopException;
use PinVandaag\PMarketAPIDevelop\Model\UploadedFileContent;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareTrait;
use SensitiveParameter;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

final class APIClient
{
    use LoggerAwareTrait;
    use DeveloperApiTrait;

    private readonly SerializerInterface $serializer;
    private ?string $apiKey = null;
    private ?string $apiSecret = null;
    private string $contentLanguage = 'en';
    private string $timeZone = 'UTC';

    public function __construct(
        private readonly ClientInterface $client,
        private string $baseUri = '',
        ?SerializerInterface $serializer = null,
    ) {
        $this->baseUri = rtrim($this->baseUri, '/');
        $this->serializer = $serializer ?? new Serializer(
            [new ObjectNormalizer()],
            [new JsonEncoder()],
        );
    }

    public function setBaseUri(string $baseUri): self
    {
        $this->baseUri = rtrim($baseUri, '/');

        return $this;
    }

    public function setApiKey(#[SensitiveParameter] string $apiKey): self
    {
        if ($apiKey === '') {
            throw new PMarketAPIDevelopException('P Market Developer API key cannot be empty.');
        }

        $this->apiKey = $apiKey;

        return $this;
    }

    public function setApiSecret(#[SensitiveParameter] string $apiSecret): self
    {
        if ($apiSecret === '') {
            throw new PMarketAPIDevelopException('P Market Developer API secret cannot be empty.');
        }

        $this->apiSecret = $apiSecret;

        return $this;
    }

    public function setContentLanguage(string $contentLanguage): self
    {
        $contentLanguage = trim($contentLanguage);
        if ($contentLanguage === '') {
            throw new PMarketAPIDevelopException('Content language cannot be empty.');
        }

        $this->contentLanguage = $contentLanguage;

        return $this;
    }

    public function setTimeZone(string $timeZone): self
    {
        $timeZone = trim($timeZone);
        if ($timeZone === '') {
            throw new PMarketAPIDevelopException('Time zone cannot be empty.');
        }

        $this->timeZone = $timeZone;

        return $this;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $responseClass
     * @param array<string, string> $query
     * @param array<string, string> $headers
     *
     * @return T
     */
    private function resultData(
        string $method,
        string $endpoint,
        string $responseClass,
        string $actionDescription,
        array $query = [],
        array|object $body = [],
        array $headers = [],
    ): object {
        $options = ['headers' => $this->defaultHeaders() + $headers];

        if ($body !== [] || is_object($body)) {
            $options['headers'] += ['Content-Type' => 'application/json;charset=utf-8'];
            $options['body'] = $this->serialize($body, $actionDescription);
        }

        $response = $this->request($method, $endpoint, $query, $options, $actionDescription);

        return $this->deserializeResultData($response, $responseClass, $actionDescription);
    }

    /**
     * @param array<string, string> $query
     * @param array<string, string> $headers
     */
    private function resultRawData(
        string $method,
        string $endpoint,
        string $actionDescription,
        array $query = [],
        array|object $body = [],
        array $headers = [],
    ): mixed {
        $options = ['headers' => $this->defaultHeaders() + $headers];

        if ($body !== [] || is_object($body)) {
            $options['headers'] += ['Content-Type' => 'application/json;charset=utf-8'];
            $options['body'] = $this->serialize($body, $actionDescription);
        }

        $response = $this->request($method, $endpoint, $query, $options, $actionDescription);
        $decoded = $this->decodeSuccessfulResult($response, $actionDescription);

        return $decoded['data'] ?? null;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $responseClass
     * @param array<string, string> $query
     * @param list<array<string, mixed>> $multipart
     *
     * @return T
     */
    private function multipartResultData(
        string $endpoint,
        string $responseClass,
        string $actionDescription,
        array $multipart,
        array $query = [],
    ): object {
        $response = $this->request(
            method: 'POST',
            endpoint: $endpoint,
            query: $query,
            options: [
                'headers' => $this->defaultHeaders(),
                'multipart' => $multipart,
            ],
            actionDescription: $actionDescription,
        );

        return $this->deserializeResultData($response, $responseClass, $actionDescription);
    }

    /**
     * @param array<string, string> $query
     * @param list<array<string, mixed>> $multipart
     */
    private function multipartResultRawData(
        string $endpoint,
        string $actionDescription,
        array $multipart,
        array $query = [],
    ): mixed {
        $response = $this->request(
            method: 'POST',
            endpoint: $endpoint,
            query: $query,
            options: [
                'headers' => $this->defaultHeaders(),
                'multipart' => $multipart,
            ],
            actionDescription: $actionDescription,
        );

        $decoded = $this->decodeSuccessfulResult($response, $actionDescription);

        return $decoded['data'] ?? null;
    }

    private function emptyResult(
        string $method,
        string $endpoint,
        string $actionDescription,
        array $query = [],
        array $headers = [],
        array|object $body = [],
    ): void {
        $options = ['headers' => $this->defaultHeaders() + $headers];

        if ($body !== [] || is_object($body)) {
            $options['headers'] += ['Content-Type' => 'application/json;charset=utf-8'];
            $options['body'] = $this->serialize($body, $actionDescription);
        }

        $response = $this->request($method, $endpoint, $query, $options, $actionDescription);
        $this->deserializeEmptyResult($response, $actionDescription);
    }

    /**
     * @param list<array<string, mixed>> $multipart
     */
    private function emptyMultipartResult(
        string $endpoint,
        string $actionDescription,
        array $multipart,
        array $query = [],
    ): void {
        $response = $this->request(
            method: 'POST',
            endpoint: $endpoint,
            query: $query,
            options: [
                'headers' => $this->defaultHeaders(),
                'multipart' => $multipart,
            ],
            actionDescription: $actionDescription,
        );

        $this->deserializeEmptyResult($response, $actionDescription);
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, string> $query
     */
    private function request(
        string $method,
        string $endpoint,
        array $query,
        array $options,
        string $actionDescription,
    ): ResponseInterface {
        [$signedQueryString, $signature] = $this->signedQueryAndSignature($query);
        $options['headers'] = ($options['headers'] ?? []) + ['signature' => $signature];

        try {
            $response = $this->client->request(
                $method,
                $this->uri($endpoint),
                $options + [
                    'query' => $signedQueryString,
                    'connect_timeout' => 8.0,
                    'http_errors' => false,
                    'timeout' => 120.0,
                    'verify' => true,
                ],
            );

            $this->logger?->debug('P Market Developer API request completed.', [
                'method' => $method,
                'endpoint' => $endpoint,
                'statusCode' => $response->getStatusCode(),
            ]);

            return $response;
        } catch (Throwable $exception) {
            throw new PMarketAPIDevelopException(sprintf('Could not %s.', $actionDescription), 0, $exception);
        }
    }

    private function uri(string $endpoint): string
    {
        return $this->baseUri . '/' . ltrim($endpoint, '/');
    }

    /** @return array<string, string> */
    private function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'SDK-Language' => 'Java',
            'SDK-Version' => '10.2.1',
            'content-language' => $this->contentLanguage,
            'Time-Zone' => $this->timeZone,
        ];
    }

    /**
     * Developer API signing follows the official Java SDK: append devKey and timestamp,
     * build the query in insertion order and send upper-case MD5(query + apiSecret).
     *
     * @param array<string, string> $query
     * @return array{0: string, 1: string}
     */
    private function signedQueryAndSignature(array $query): array
    {
        if ($this->apiKey === null || $this->apiKey === '') {
            throw new PMarketAPIDevelopException('P Market Developer API key has not been configured.');
        }

        if ($this->apiSecret === null || $this->apiSecret === '') {
            throw new PMarketAPIDevelopException('P Market Developer API secret has not been configured.');
        }

        $query['devKey'] = $this->apiKey;
        $query['timestamp'] = (string) (int) floor(microtime(true) * 1000);

        $queryString = $this->javaBuildQuery($query);
        $signature = strtoupper(md5($queryString . $this->apiSecret));

        return [$queryString, $signature];
    }

    /** @param array<string, string> $query */
    private function javaBuildQuery(array $query): string
    {
        $parts = [];
        foreach ($query as $name => $value) {
            if ($name === '' || $value === '') {
                continue;
            }

            $parts[] = $name . '=' . urlencode($value);
        }

        return implode('&', $parts);
    }

    /**
     * @template T of object
     * @param class-string<T> $responseClass
     * @return T
     */
    private function deserializeResultData(
        ResponseInterface $response,
        string $responseClass,
        string $actionDescription,
    ): object {
        $decoded = $this->decodeSuccessfulResult($response, $actionDescription);

        if (!array_key_exists('data', $decoded) || $decoded['data'] === null) {
            throw new PMarketAPIDevelopException(sprintf(
                'P Market Developer API returned empty data while trying to %s.',
                $actionDescription,
            ));
        }

        try {
            /** @var T $result */
            $result = $this->serializer->denormalize($decoded['data'], $responseClass);
        } catch (SerializerException $exception) {
            throw new PMarketAPIDevelopException(
                sprintf('Could not deserialize P Market Developer API response for %s.', $actionDescription),
                0,
                $exception,
            );
        }

        return $result;
    }

    private function deserializeEmptyResult(ResponseInterface $response, string $actionDescription): void
    {
        $body = trim((string) $response->getBody());
        if ($body === '' && $response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            return;
        }

        $this->decodeSuccessfulResult($response, $actionDescription);
    }

    /** @return array<string, mixed> */
    private function decodeSuccessfulResult(ResponseInterface $response, string $actionDescription): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new PMarketAPIDevelopException(
                $this->errorMessageFromResponseBody($body, $actionDescription, $statusCode),
                $statusCode,
            );
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new PMarketAPIDevelopException(sprintf(
                'Could not decode P Market Developer API response for %s.',
                $actionDescription,
            ));
        }

        if (($decoded['businessCode'] ?? null) !== 0) {
            throw new PMarketAPIDevelopException(
                $this->resultErrorMessage($decoded, $actionDescription, $statusCode),
                (int) ($decoded['businessCode'] ?? 0),
            );
        }

        return $decoded;
    }

    private function errorMessageFromResponseBody(string $body, string $actionDescription, int $statusCode): string
    {
        $trimmedBody = trim($body);
        if ($trimmedBody === '') {
            return sprintf(
                'P Market Developer API request failed while trying to %s with HTTP %d.',
                $actionDescription,
                $statusCode,
            );
        }

        $decoded = json_decode($trimmedBody, true);
        if (is_array($decoded)) {
            $message = $decoded['message'] ?? null;
            if (is_string($message) && $message !== '') {
                $nested = json_decode($message, true);
                if (is_array($nested)) {
                    return $this->resultErrorMessage($nested, $actionDescription, $statusCode);
                }

                return $message;
            }

            return $this->resultErrorMessage($decoded, $actionDescription, $statusCode);
        }

        return $trimmedBody;
    }

    /** @param array<string, mixed> $decoded */
    private function resultErrorMessage(array $decoded, string $actionDescription, int $statusCode): string
    {
        if (!empty($decoded['validationErrors']) && is_array($decoded['validationErrors'])) {
            return implode('; ', array_map('strval', $decoded['validationErrors']));
        }

        $businessCode = $decoded['businessCode'] ?? null;
        $message = $decoded['message'] ?? null;

        if (is_scalar($businessCode) && is_scalar($message)) {
            return sprintf('P Market Developer API businessCode %s: %s', (string) $businessCode, (string) $message);
        }

        if (is_scalar($message)) {
            return (string) $message;
        }

        return sprintf(
            'P Market Developer API request failed while trying to %s with HTTP %d.',
            $actionDescription,
            $statusCode,
        );
    }

    private function serialize(array|object $value, string $actionDescription): string
    {
        try {
            return $this->serializer->serialize($value, 'json');
        } catch (SerializerException $exception) {
            throw new PMarketAPIDevelopException(
                sprintf('Could not serialize request while trying to %s.', $actionDescription),
                0,
                $exception,
            );
        }
    }

    private function assertPositiveInteger(mixed $value, string $fieldName): int
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === null || $value === '' || filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
            throw new PMarketAPIDevelopException(sprintf('%s cannot be null and cannot be less than 1.', $fieldName));
        }

        return (int) $value;
    }

    private function assertNonEmptyString(mixed $value, string $fieldName): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new PMarketAPIDevelopException(sprintf('%s cannot be null or empty.', $fieldName));
        }

        return trim($value);
    }

    /** @param list<UploadedFileContent> $files */
    private function assertUploadedFiles(array $files, string $fieldName, int $minimum = 0): void
    {
        if (count($files) < $minimum) {
            throw new PMarketAPIDevelopException(sprintf('%s must contain at least %d files.', $fieldName, $minimum));
        }

        foreach ($files as $file) {
            if (!$file instanceof UploadedFileContent) {
                throw new PMarketAPIDevelopException(sprintf('%s must contain UploadedFileContent objects.', $fieldName));
            }
        }
    }

    /** @return array<string, mixed> */
    private function multipartFile(string $name, UploadedFileContent $file): array
    {
        return [
            'name' => $name,
            'contents' => $file->bytesContent,
            'filename' => $file->originalFilename,
            'headers' => ['Content-Type' => $file->contentType],
        ];
    }
}
