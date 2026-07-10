<?php
declare(strict_types=1);
namespace PinVandaag\PMarketAPIDevelop;
use GuzzleHttp\ClientInterface;
use PinVandaag\PMarketAPIDevelop\Client\DeveloperAPIClient;
use Symfony\Component\Serializer\SerializerInterface;
final class PMarketAPIDevelopClient extends DeveloperAPIClient {}
