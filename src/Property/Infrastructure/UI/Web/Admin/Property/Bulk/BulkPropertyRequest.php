<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Property\Bulk;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;

final class BulkPropertyRequest extends BulkRequest
{
    public ?AccountId $accountId = null;
}