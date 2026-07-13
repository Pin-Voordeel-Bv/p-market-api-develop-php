<?php
declare(strict_types=1); namespace PinVandaag\PMarketAPIDevelop\Model;
final readonly class AppDetail { public function __construct(public int|string $id,public string $name,public string $type,public string $status){} }
