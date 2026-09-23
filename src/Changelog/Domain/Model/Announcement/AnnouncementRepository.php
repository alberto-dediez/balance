<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\Announcement;

interface AnnouncementRepository
{
    public function add(Announcement $announcement): void;

    public function remove(Announcement $announcement): void;

    public function find(AnnouncementId $announcementId): ?Announcement;

    /** @return list<Announcement> */
    public function findByFilter(AnnouncementFilter $filter): array;

    public function totalResultsByFilter(AnnouncementFilter $filter): int;

    /** @return list<Announcement> */
    public function findScheduledToPublishUntil(\DateTimeImmutable $until): array;
}
