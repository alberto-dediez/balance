<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\JobProgress\JobProgressId;
use Doctrine\DBAL\Types\Type;

class JobProgressIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'job_progress_id';
    }

    /** @return class-string<AggregateRootId> */
    public function getClassName(): string
    {
        return JobProgressId::class;
    }
}
