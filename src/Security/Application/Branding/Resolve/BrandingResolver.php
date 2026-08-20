<?php

declare(strict_types=1);

namespace App\Security\Application\Branding\Resolve;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Branding\BrandingTokens;

interface BrandingResolver
{
    public function resolve(?AccountId $accountId): BrandingTokens;
}
