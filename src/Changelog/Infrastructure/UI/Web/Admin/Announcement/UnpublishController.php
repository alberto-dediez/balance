<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\Admin\Announcement;

use App\Changelog\Application\Announcement\Unpublish\UnpublishAnnouncementRequest;
use App\Changelog\Application\Announcement\Unpublish\UnpublishAnnouncementService;
use App\Changelog\Domain\Model\Announcement\AnnouncementRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/announcements/unpublish/{announcementId}', name: 'admin_announcements_unpublish')]
#[CsrfProtected('unpublish')]
#[IsGranted('ROLE_ADMIN')]
class UnpublishController extends AbstractController
{
    use GetAnnouncementFromIdTrait;

    public function __invoke(
        string $announcementId,
        LoggedUser $loggedUser,
        AnnouncementRepository $announcementRepository,
        UnpublishAnnouncementService $unpublishAnnouncement,
    ): Response {
        $announcement = $this->getAnnouncementFromId($announcementId, $announcementRepository);

        try {
            $response = $unpublishAnnouncement(new UnpublishAnnouncementRequest($announcement->id()), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $this->addFlash('success', $response->wasScheduled
            ? new TranslatableMessage('Programación cancelada')
            : new TranslatableMessage('Novedad despublicada'));

        return $this->redirectToRoute('admin_announcements');
    }
}
