<?php

declare(strict_types=1);

namespace App\Library\Application\Folder;

use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderFilter;
use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Folder\FolderRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;

class CurrentAccountFolders
{
    public function __construct(
        private readonly FolderRepository $repository,
        private readonly CurrentAccountIdProvider $currentAccount,
    ) {}

    /**
     * @return list<Folder>
     */
    public function all(): array
    {
        return $this->repository->findByFilter($this->filter());
    }

    public function find(FolderId $folderId): ?Folder
    {
        $folder = $this->repository->find($folderId);

        if (!$folder instanceof Folder) {
            return null;
        }

        $accountId = $this->currentAccount->currentAccountId();

        if (!$folder->accountId() instanceof AccountId) {
            return $accountId instanceof AccountId ? null : $folder;
        }

        return $accountId instanceof AccountId && $folder->accountId()->equals($accountId) ? $folder : null;
    }

    /**
     * @return list<Folder>
     */
    public function matchingName(string $name): array
    {
        if ('' === $name) {
            return [];
        }

        return $this->repository->findByFilter($this->filter($name));
    }

    private function filter(?string $name = null): FolderFilter
    {
        $accountId = $this->currentAccount->currentAccountId();

        return new FolderFilter(
            name: $name,
            accountId: $accountId,
            withoutAccount: !$accountId instanceof AccountId,
            limit: 0,
        );
    }
}
