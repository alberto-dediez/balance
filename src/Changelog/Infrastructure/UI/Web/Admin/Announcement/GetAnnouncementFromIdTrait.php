<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\Admin\Announcement;

use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\AnnouncementRepository;

trait GetAnnouncementFromIdTrait
{
    private function getAnnouncementFromId(string $announcementId, AnnouncementRepository $repository): Announcement
    {
        $announcement = $repository->find(new AnnouncementId($announcementId));

        if (null == $announcement) {
            throw $this->createNotFoundException('Novedad no encontrada');
        }

        return $announcement;
    }
}
