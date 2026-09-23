<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\Admin\Announcement;

use App\Changelog\Application\Announcement\Publish\PublishAnnouncementRequest;
use App\Changelog\Application\Announcement\Publish\PublishAnnouncementResponse;
use App\Changelog\Application\Announcement\Publish\PublishAnnouncementService;
use App\Changelog\Domain\Model\Announcement\AnnouncementRepository;
use App\Changelog\Domain\Model\Announcement\AnnouncementStatus;
use App\Changelog\Infrastructure\Symfony\Form\PublishAnnouncementType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CurrentTimezone\CurrentTimezoneProvider;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/announcements/publish/{announcementId}', name: 'admin_announcements_publish')]
#[IsGranted('ROLE_ADMIN')]
class PublishController extends AbstractController
{
    use GetAnnouncementFromIdTrait;

    public function __construct(private readonly CurrentTimezoneProvider $timezoneProvider) {}

    public function __invoke(
        string $announcementId,
        AnnouncementRepository $announcementRepository,
        LoggedUser $loggedUser,
        PublishAnnouncementService $publishAnnouncement,
        Request $request,
    ): Response {
        $announcement = $this->getAnnouncementFromId($announcementId, $announcementRepository);

        if ($announcement->isPublished()) {
            $this->addFlash('warning', new TranslatableMessage('La novedad ya estaba publicada'));

            return $this->redirectToRoute('admin_announcements');
        }

        $form = $this->createForm(PublishAnnouncementType::class, new PublishAnnouncementRequest(
            announcementId: $announcement->id(),
            publishAt: $announcement->scheduledAt(),
            notifyUsers: $announcement->notifyOnPublish(),
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $response = $publishAnnouncement($form->getData(), $loggedUser());
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            }

            $this->addFlash('success', $this->flashFor($response));

            return $this->redirectToRoute('admin_announcements');
        }

        return $this->render('@changelog/admin/announcements/publish.html.twig', [
            'announcement' => $announcement,
            'defaultLocale' => LocaleGetter::default(),
            'form' => $form->createView(),
        ]);
    }

    private function flashFor(PublishAnnouncementResponse $response): TranslatableMessage
    {
        if (AnnouncementStatus::Scheduled === $response->status) {
            return new TranslatableMessage('Novedad programada para el {date}', ['{date}' => $response->scheduledAt?->setTimezone($this->timezoneProvider->currentTimezone())->format('d/m/Y H:i')]);
        }

        if ($response->notified) {
            return new TranslatableMessage('Novedad publicada. Se está notificando a los usuarios');
        }

        return new TranslatableMessage('Novedad publicada');
    }
}
