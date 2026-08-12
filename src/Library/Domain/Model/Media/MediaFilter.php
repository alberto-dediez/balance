<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Media;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class MediaFilter
{
    use Filter;

    public ?bool $withoutAccount = null;

    public ?bool $includeShared = null;

    /**
     * @param list<MediaType> $types
     */
    public function __construct(
        public ?string $name = null,
        public ?MediaType $type = null,
        public ?AccountId $accountId = null,
        public array $types = [],
        public ?MediaVisibility $visibility = null,
        ?bool $withoutAccount = null,
        ?bool $includeShared = null,
        int $limit = 24,
    ) {
        $this->withoutAccount = $withoutAccount;
        $this->includeShared = $includeShared;
        $this->limit = $limit;
        $this->orderBy = 'registeredAt';
        $this->orderWay = 'DESC';
    }
}
