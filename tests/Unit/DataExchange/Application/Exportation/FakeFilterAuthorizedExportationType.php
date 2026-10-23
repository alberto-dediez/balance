<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Application\Exportation;

use App\DataExchange\Domain\Model\Exportation\FilterAuthorizedExportationType;
use App\Security\Domain\Model\User\User;

class FakeFilterAuthorizedExportationType extends FakeExportationType implements FilterAuthorizedExportationType
{
    public function __construct(
        private readonly bool $authorizedForFilter = true,
        bool $authorized = true,
        string $code = 'users',
    ) {
        parent::__construct(authorized: $authorized, code: $code);
    }

    public function isAuthorizedForFilter(object $filter, User $user): bool
    {
        return $this->authorizedForFilter;
    }
}
