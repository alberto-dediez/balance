<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Model\Folder;

use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderId;
use App\Security\Domain\Model\Account\AccountId;

class FolderMother
{
    public static function fromName(string $name): Folder
    {
        return self::from(FolderId::generate(), $name);
    }

    public static function fromNameAndId(string $name, string $id): Folder
    {
        return self::from(new FolderId($id), $name);
    }

    public static function fromNameAndAccount(string $name, AccountId $accountId): Folder
    {
        return self::from(FolderId::generate(), $name, $accountId);
    }

    public static function subfolderOf(Folder $parent, string $name, ?string $id = null): Folder
    {
        return self::from(
            null === $id ? FolderId::generate() : new FolderId($id),
            $name,
            $parent->accountId(),
            $parent->id(),
        );
    }

    public static function from(
        FolderId $folderId,
        string $name = 'Contratos',
        ?AccountId $accountId = null,
        ?FolderId $parentId = null,
    ): Folder {
        $folder = new Folder(id: $folderId);

        $folder->register($name, $accountId, $parentId);

        return $folder;
    }
}
