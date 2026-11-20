<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Folder;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class FolderId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
