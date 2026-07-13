<?php
declare(strict_types=1); namespace PinVandaag\PMarketAPIDevelop\Model;
final readonly class EditAppKeySecretRequest { public function __construct(public string $appKey,public string $appSecret){} }
