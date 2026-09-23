<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\List;

use App\Changelog\Domain\Model\Announcement\AnnouncementFilter;
use App\Shared\Application\CommandBus\Command;

class ListAnnouncementsRequest extends AnnouncementFilter implements Command {}
