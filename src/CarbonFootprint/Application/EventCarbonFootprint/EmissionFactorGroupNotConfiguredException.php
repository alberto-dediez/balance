<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint;

use App\Security\Domain\Model\Account\AccountId;

class EmissionFactorGroupNotConfiguredException extends \RuntimeException
{
    public function __construct(AccountId $accountId)
    {
        parent::__construct(sprintf(
            'Account "%s" has no emission factor group configured and no "default" emission factor group exists.',
            (string) $accountId,
        ));
    }
}
