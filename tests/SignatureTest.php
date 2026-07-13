<?php
declare(strict_types=1); namespace PinVandaag\PMarketAPIDevelop\Tests;
use PHPUnit\Framework\TestCase; use PinVandaag\PMarketAPIDevelop\PMarketAPIDevelopClient;
final class SignatureTest extends TestCase { public function testConstructs():void{self::assertInstanceOf(PMarketAPIDevelopClient::class,new PMarketAPIDevelopClient());} }
