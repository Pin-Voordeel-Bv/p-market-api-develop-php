<?php
declare(strict_types=1);
namespace PinVandaag\PMarketAPIDevelop\Model;
final readonly class PageResult
{
    public function __construct(
        public int $pageNo,
        public int $limit,
        public int $totalCount,
        public bool $hasNext,
        public array $dataSet,
    ) {}
}
