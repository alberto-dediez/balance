<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Persistence\InMemory\Repository;

use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaFilter;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryMediaRepository implements MediaRepository
{
    /**
     * @param Media[] $media
     */
    public function __construct(private array $media = []) {}

    public function add(Media $media): void
    {
        $this->media[(string) $media->id()] = $media;
    }

    public function remove(Media $media): void
    {
        if (!isset($this->media[(string) $media->id()])) {
            return;
        }

        unset($this->media[(string) $media->id()]);
    }

    public function find(MediaId $mediaId): ?Media
    {
        return (isset($this->media[(string) $mediaId]) && false === $this->media[(string) $mediaId]->isRemoved())
            ? $this->media[(string) $mediaId]
            : null
        ;
    }

    /**
     * @return list<Media>
     */
    public function findByFilter(MediaFilter $filter): array
    {
        $results = [];

        if (0 === count($this->media)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->media as $media) {
            if (!$this->matches($media, $filter)) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $media;
        }

        return $results;
    }

    public function totalResultsByFilter(MediaFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }

    private function matches(Media $media, MediaFilter $filter): bool
    {
        if ($media->isRemoved()) {
            return false;
        }

        if (
            !in_array($filter->name, [null, '', '0'], true)
            && !str_contains(strtolower($media->name()), strtolower($filter->name))
            && !str_contains(strtolower($media->fileName()), strtolower($filter->name))
        ) {
            return false;
        }

        if (null !== $filter->type && $filter->type !== $media->type()) {
            return false;
        }

        if ([] !== $filter->types && !in_array($media->type(), $filter->types, true)) {
            return false;
        }

        if ($filter->visibility instanceof MediaVisibility && !$media->visibility()->satisfies($filter->visibility)) {
            return false;
        }

        return $this->matchesAccountScope($media, $filter);
    }

    private function matchesAccountScope(Media $media, MediaFilter $filter): bool
    {
        if (true === $filter->includeShared && $media->isShared()) {
            return true;
        }

        if (true === $filter->withoutAccount && $media->accountId() instanceof AccountId) {
            return false;
        }

        return !(
            $filter->accountId instanceof AccountId
            && (!$media->accountId() instanceof AccountId || !$media->accountId()->equals($filter->accountId))
        );
    }
}
