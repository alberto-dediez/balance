<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Persistence\InMemory\Repository;

use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderFilter;
use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Folder\FolderRepository;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryFolderRepository implements FolderRepository
{
    /**
     * @param array<string, Folder> $folders
     */
    public function __construct(private array $folders = []) {}

    public function add(Folder $folder): void
    {
        $this->folders[(string) $folder->id()] = $folder;
    }

    public function remove(Folder $folder): void
    {
        if (!isset($this->folders[(string) $folder->id()])) {
            return;
        }

        unset($this->folders[(string) $folder->id()]);
    }

    public function find(FolderId $folderId): ?Folder
    {
        return $this->folders[(string) $folderId] ?? null;
    }

    public function findOneByAccountAndName(?AccountId $accountId, string $name, ?FolderId $parentId = null): ?Folder
    {
        foreach ($this->folders as $folder) {
            if (
                mb_strtolower($folder->name()) === mb_strtolower($name)
                && $this->belongsTo($folder, $accountId)
                && $this->hasParent($folder, $parentId)
            ) {
                return $folder;
            }
        }

        return null;
    }

    /**
     * @return list<Folder>
     */
    public function findByFilter(FolderFilter $filter): array
    {
        $results = [];

        foreach ($this->folders as $folder) {
            if (
                !in_array($filter->name, [null, '', '0'], true)
                && !str_contains(strtolower($folder->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if ($filter->accountId instanceof AccountId && !$this->belongsTo($folder, $filter->accountId)) {
                continue;
            }

            if (true === $filter->withoutAccount && $folder->accountId() instanceof AccountId) {
                continue;
            }

            if ($filter->parentId instanceof FolderId && !$this->hasParent($folder, $filter->parentId)) {
                continue;
            }

            $results[] = $folder;
        }

        $way = 'ASC' === $filter->orderWay ? 1 : -1;

        usort($results, static fn(Folder $a, Folder $b): int => $way * match ($filter->orderBy) {
            'registeredAt' => $a->registeredAt() <=> $b->registeredAt(),
            default => strcasecmp($a->name(), $b->name()),
        });

        if ($filter->limit > 0) {
            $results = array_slice($results, $filter->page * $filter->limit, $filter->limit);
        }

        return $results;
    }

    public function totalResultsByFilter(FolderFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }

    private function belongsTo(Folder $folder, ?AccountId $accountId): bool
    {
        if (!$accountId instanceof AccountId) {
            return !$folder->accountId() instanceof AccountId;
        }

        return $folder->accountId() instanceof AccountId && $folder->accountId()->equals($accountId);
    }

    private function hasParent(Folder $folder, ?FolderId $parentId): bool
    {
        if (!$parentId instanceof FolderId) {
            return !$folder->parentId() instanceof FolderId;
        }

        return $folder->parentId() instanceof FolderId && $folder->parentId()->equals($parentId);
    }
}
