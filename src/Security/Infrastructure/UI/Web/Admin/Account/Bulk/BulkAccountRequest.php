<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\Account\Bulk;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;

final class BulkAccountRequest extends BulkRequest
{
    public ?AccountId $consultantAccountId = null;
}
