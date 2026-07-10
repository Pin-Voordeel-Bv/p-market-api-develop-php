<?php
declare(strict_types=1);
namespace PinVandaag\PMarketAPIDevelop\Tests;
use PHPUnit\Framework\TestCase;
use PinVandaag\PMarketAPIDevelop\PMarketAPIDevelopClient;
final class SmokeTest extends TestCase
{
    public function testClientCanBeConstructed(): void
    {
        self::assertInstanceOf(PMarketAPIDevelopClient::class, new PMarketAPIDevelopClient());
    }
}
