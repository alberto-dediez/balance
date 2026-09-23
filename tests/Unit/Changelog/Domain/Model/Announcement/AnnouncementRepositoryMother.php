<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Domain\Model\Announcement;

use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Infrastructure\Persistence\InMemory\Repository\InMemoryAnnouncementRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class AnnouncementRepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemoryAnnouncementRepository
    {
        FakeLocaleSetter::set($locale);

        return new InMemoryAnnouncementRepository();
    }

    public static function withAnnouncements(Announcement ...$announcements): InMemoryAnnouncementRepository
    {
        $repository = self::inMemory();

        foreach ($announcements as $announcement) {
            $repository->add($announcement);
        }

        return $repository;
    }
}
