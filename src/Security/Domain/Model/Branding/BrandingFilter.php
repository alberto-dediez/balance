<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Branding;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class BrandingFilter
{
    use Filter;

    public function __construct(
        public ?AccountId $accountId = null,
        public ?string $themeSlug = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
