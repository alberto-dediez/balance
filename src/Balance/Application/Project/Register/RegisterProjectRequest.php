<?php

declare(strict_types=1);

namespace App\Balance\Application\Project\Register;

use App\Balance\Domain\Model\Project\ProjectStatus;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Library\Domain\Model\Media\MediaId;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Shared\Application\CommandBus\Command;

class RegisterProjectRequest implements Command
{
    /** @param array<MediaId> $gallery */
    public function __construct(
        public string $name = '',
        public ?string $description = '',
        public ?DestinationId $location = null,
        public array $gallery = [],
        public ?ProjectType $projectType = null,
        public ?ProjectStatus $projectStatus = null,
        public ?string $mitecoId = null,
    ) {}
}
