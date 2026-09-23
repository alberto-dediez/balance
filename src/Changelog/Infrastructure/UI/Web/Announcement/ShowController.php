<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\Announcement;

use App\Changelog\Application\Announcement\View\ViewAnnouncementRequest;
use App\Changelog\Application\Announcement\View\ViewAnnouncementService;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\AnnouncementNotFoundException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/announcements/{announcementId}', name: 'announcements_show', requirements: ['announcementId' => '[0-9a-fA-F-]{36}'])]
#[IsGranted('ROLE_USER')]
class ShowController extends AbstractController
{
    public function __invoke(string $announcementId, ViewAnnouncementService $viewAnnouncement, LoggedUser $loggedUser): Response
    {
        try {
            $response = $viewAnnouncement(new ViewAnnouncementRequest(new AnnouncementId($announcementId)), $loggedUser());
        } catch (AnnouncementNotFoundException|UnauthorizedActionException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        return $this->render('@changelog/announcements/show.html.twig', [
            'announcement' => $response->announcement,
        ]);
    }
}
