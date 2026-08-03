<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\Doctrine\Type;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ProjectIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'project_id';
    }

    public function getClassName(): string
    {
        return ProjectId::class;
    }
}
