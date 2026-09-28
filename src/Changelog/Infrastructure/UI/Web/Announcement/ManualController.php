<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\Announcement;

use App\Changelog\Application\Announcement\DownloadManual\DownloadAnnouncementManualRequest;
use App\Changelog\Application\Announcement\DownloadManual\DownloadAnnouncementManualService;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\AnnouncementNotFoundException;
use App\Changelog\Domain\Model\ChangelogEntry\ManualNotFoundException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/announcements/{announcementId}/manual', name: 'announcements_manual', requirements: ['announcementId' => '[0-9a-fA-F-]{36}'])]
#[IsGranted('ROLE_USER')]
class ManualController extends AbstractController
{
    public function __invoke(string $announcementId, Request $request, DownloadAnnouncementManualService $downloadManual, LoggedUser $loggedUser): RedirectResponse
    {
        try {
            $response = $downloadManual(new DownloadAnnouncementManualRequest(new AnnouncementId($announcementId)), $loggedUser());
        } catch (AnnouncementNotFoundException|UnauthorizedActionException|ManualNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        $parameters = ['mediaId' => (string) $response->mediaId];

        if ($request->query->getBoolean('download')) {
            $parameters['download'] = 1;
        }

        return $this->redirectToRoute('library_media_show', $parameters);
    }
}
