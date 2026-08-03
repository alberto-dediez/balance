<?php

declare(strict_types=1);

namespace App\Balance\Application\Project\Update;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectStatus;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Library\Domain\Model\Media\MediaId;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Shared\Application\CommandBus\Command;

class UpdateProjectRequest implements Command
{
    /** @param array<MediaId> $gallery */
    public function __construct(
        public ProjectId $projectId,
        public string $name,
        public ?string $description,
        public ?DestinationId $location,
        public array $gallery,
        public ?ProjectType $projectType,
        public ?ProjectStatus $projectStatus,
        public ?string $mitecoId,
    ) {}
}
