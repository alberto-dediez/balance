<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Folder;

use App\Security\Domain\Model\Account\AccountId;

interface FolderRepository
{
    public function add(Folder $folder): void;

    public function remove(Folder $folder): void;

    public function find(FolderId $folderId): ?Folder;

    public function findOneByAccountAndName(?AccountId $accountId, string $name, ?FolderId $parentId = null): ?Folder;

    /** @return list<Folder> */
    public function findByFilter(FolderFilter $filter): array;

    public function totalResultsByFilter(FolderFilter $filter): int;
}
