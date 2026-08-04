<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Update;

use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateMediaResponse implements CommandResponse
{
    public function __construct(
        public MediaId $mediaId,
        public string $name,
        public ?string $description = null,
        public ?string $alternativeText = null,
        public MediaVisibility $visibility = MediaVisibility::Account,
    ) {}
}
