<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Infrastructure\Persistence\Doctrine\Repository;

use App\Changelog\Domain\Model\Announcement\AnnouncementRepository;
use App\Changelog\Infrastructure\Persistence\Doctrine\Repository\DoctrineAnnouncementRepository;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineAnnouncementRepositoryTest extends AnnouncementRepositoryTest
{
    protected function createSUT(): AnnouncementRepository
    {
        return new DoctrineAnnouncementRepository($this->manager());
    }

    protected function clearPersistence(): void
    {
        $this->manager()->clear();
    }

    private function manager(): EntityManagerInterface
    {
        return $this->getContainer()->get(EntityManagerInterface::class);
    }
}
