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
    public const PATH_SEPARATOR = ' / ';

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

    /**
     * @return list<Folder>
     */
    public function roots(): array
    {
        return array_values(array_filter($this->all(), static fn(Folder $folder): bool => !$folder->isSubfolder()));
    }

    /**
     * @return list<Folder>
     */
    public function subfoldersOf(FolderId $folderId): array
    {
        return $this->childrenOf($this->all(), $folderId);
    }

    /**
     * @return list<array{folder: Folder, subfolders: list<Folder>}>
     */
    public function tree(): array
    {
        $folders = $this->all();
        $tree = [];

        foreach ($folders as $root) {
            if ($root->isSubfolder()) {
                continue;
            }

            $tree[] = ['folder' => $root, 'subfolders' => $this->childrenOf($folders, $root->id())];
        }

        return $tree;
    }

    /**
     * @return array<string, string>
     */
    public function paths(): array
    {
        $folders = $this->all();
        $paths = [];

        foreach ($folders as $root) {
            if ($root->isSubfolder()) {
                continue;
            }

            $paths[(string) $root->id()] = $root->name();

            foreach ($this->childrenOf($folders, $root->id()) as $subfolder) {
                $paths[(string) $subfolder->id()] = $root->name() . self::PATH_SEPARATOR . $subfolder->name();
            }
        }

        return $paths;
    }

    /**
     * @return list<FolderId>
     */
    public function idsWithSubfolders(FolderId $folderId): array
    {
        return [
            $folderId,
            ...array_map(static fn(Folder $folder): FolderId => $folder->id(), $this->subfoldersOf($folderId)),
        ];
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

        $matches = $this->repository->findByFilter($this->filter($name));
        $folders = $this->all();
        $result = [];

        foreach ($matches as $match) {
            $result[(string) $match->id()] = $match;

            if ($match->isSubfolder()) {
                continue;
            }

            foreach ($this->childrenOf($folders, $match->id()) as $subfolder) {
                $result[(string) $subfolder->id()] = $subfolder;
            }
        }

        return array_values($result);
    }

    /**
     * @param list<Folder> $folders
     *
     * @return list<Folder>
     */
    private function childrenOf(array $folders, FolderId $folderId): array
    {
        return array_values(array_filter(
            $folders,
            static fn(Folder $folder): bool => $folder->parentId() instanceof FolderId && $folder->parentId()->equals($folderId),
        ));
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
