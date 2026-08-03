<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Media;

interface MediaRepository
{
    public function add(Media $media): void;

    public function remove(Media $media): void;

    public function find(MediaId $mediaId): ?Media;

    /** @return list<Media> */
    public function findByFilter(MediaFilter $filter): array;

    public function totalResultsByFilter(MediaFilter $filter): int;
}
