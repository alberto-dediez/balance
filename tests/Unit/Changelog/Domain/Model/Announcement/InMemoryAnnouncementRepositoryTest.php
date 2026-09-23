<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Domain\Model\Announcement;

use App\Changelog\Domain\Model\Announcement\AnnouncementRepository;

class InMemoryAnnouncementRepositoryTest extends AnnouncementRepositoryTest
{
    protected function createSUT(): AnnouncementRepository
    {
        return AnnouncementRepositoryMother::inMemory();
    }
}
