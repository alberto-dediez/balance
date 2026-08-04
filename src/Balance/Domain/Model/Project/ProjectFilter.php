<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\Project;

use App\Property\Domain\Model\Destination\DestinationId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class ProjectFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?DestinationId $location = null,
        public ?ProjectType $projectType = null,
        public ?ProjectStatus $projectStatus = null,
        public ?string $mitecoId = null,
        public ?bool $selected = null,
        public ?AccountId $selectedByAccountId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
