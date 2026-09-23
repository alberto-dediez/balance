<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\Admin\Announcement;

use App\Changelog\Application\Announcement\Remove\RemoveAnnouncementRequest;
use App\Changelog\Application\Announcement\Remove\RemoveAnnouncementService;
use App\Changelog\Domain\Model\Announcement\AnnouncementRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/announcements/remove/{announcementId}', name: 'admin_announcements_remove')]
#[CsrfProtected('remove')]
#[IsGranted('ROLE_ADMIN')]
class RemoveController extends AbstractController
{
    use GetAnnouncementFromIdTrait;

    public function __invoke(
        string $announcementId,
        LoggedUser $loggedUser,
        AnnouncementRepository $announcementRepository,
        RemoveAnnouncementService $removeAnnouncement,
    ): Response {
        $announcement = $this->getAnnouncementFromId($announcementId, $announcementRepository);

        try {
            $removeAnnouncement(new RemoveAnnouncementRequest($announcement->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la novedad {title}', ['{title}' => $announcement->title()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la novedad {title}', ['{title}' => $announcement->title()]));
        }

        return $this->redirectToRoute('admin_announcements');
    }
}
