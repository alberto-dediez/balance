<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\Persistence\Doctrine\Type;

use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class AnnouncementIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'announcement_id';
    }

    /** @return class-string<AggregateRootId> */
    public function getClassName(): string
    {
        return AnnouncementId::class;
    }
}
