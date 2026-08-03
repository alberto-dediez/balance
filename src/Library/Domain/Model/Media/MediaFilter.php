<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Media;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class MediaFilter
{
    use Filter;

    /**
     * When true only the media not belonging to any account is returned.
     *
     * Declared with a default value on purpose: filters are stored in the session
     * and restored without calling the constructor, so a promoted property would
     * stay uninitialized for every filter serialized before this field existed.
     */
    public ?bool $withoutAccount = null;

    /**
     * @param list<MediaType> $types restricts the results to the given media types
     */
    public function __construct(
        public ?string $name = null,
        public ?MediaType $type = null,
        public ?AccountId $accountId = null,
        public array $types = [],
        ?bool $withoutAccount = null,
        int $limit = 24,
    ) {
        $this->withoutAccount = $withoutAccount;
        $this->limit = $limit;
        $this->orderBy = 'registeredAt';
        $this->orderWay = 'DESC';
    }
}
