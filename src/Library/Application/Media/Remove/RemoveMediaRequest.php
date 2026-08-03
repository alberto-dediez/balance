<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Remove;

use App\Library\Domain\Model\Media\MediaId;
use App\Shared\Application\CommandBus\Command;

class RemoveMediaRequest implements Command
{
    public function __construct(public MediaId $mediaId) {}
}
