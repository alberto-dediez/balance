<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Update;

use App\Library\Domain\Model\Media\MediaId;
use App\Shared\Application\CommandBus\Command;

class UpdateMediaRequest implements Command
{
    public function __construct(
        public MediaId $mediaId,
        public string $name = '',
        public ?string $description = null,
        public ?string $alternativeText = null,
    ) {}
}
