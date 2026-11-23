<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Integration;

use App\Shared\Domain\Model\Filter;

class IntegrationOutboxFilter
{
    use Filter;

    public function __construct(
        public ?IntegrationOutboxStatus $status = IntegrationOutboxStatus::All,
        public ?string $type = null,
        int $limit = 20,
    ) {
        $this->limit = $limit;
        $this->orderBy = 'sequence';
        $this->orderWay = 'DESC';
    }
}
