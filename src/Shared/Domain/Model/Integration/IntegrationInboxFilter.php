<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Integration;

use App\Shared\Domain\Model\Filter;

class IntegrationInboxFilter
{
    use Filter;

    public function __construct(
        public ?IntegrationInboxStatus $status = null,
        public ?string $source = null,
        public ?string $type = null,
        int $limit = 20,
    ) {
        $this->limit = $limit;
        $this->orderBy = 'receivedAt';
        $this->orderWay = 'DESC';
    }
}
