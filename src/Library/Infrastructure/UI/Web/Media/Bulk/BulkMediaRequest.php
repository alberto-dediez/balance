<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Media\Bulk;

use App\Library\Domain\Model\Folder\FolderId;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;

final class BulkMediaRequest extends BulkRequest
{
    public ?FolderId $folderId = null;
}
