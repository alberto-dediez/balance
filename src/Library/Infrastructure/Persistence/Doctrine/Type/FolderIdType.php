<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Persistence\Doctrine\Type;

use App\Library\Domain\Model\Folder\FolderId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class FolderIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'folder_id';
    }

    /** @return class-string<AggregateRootId> */
    public function getClassName(): string
    {
        return FolderId::class;
    }
}
