<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\Admin\Announcement;

use App\Changelog\Application\Announcement\Update\UpdateAnnouncementRequest;
use App\Changelog\Application\Announcement\Update\UpdateAnnouncementService;
use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementRepository;
use App\Changelog\Domain\Model\Announcement\AnnouncementTranslation;
use App\Changelog\Infrastructure\Symfony\Form\UpdateAnnouncementType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/announcements/edit/{announcementId}', name: 'admin_announcements_edit')]
#[IsGranted('ROLE_ADMIN')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetAnnouncementFromIdTrait;
    use MapsManualUploadsTrait;

    public function __invoke(
        string $announcementId,
        AnnouncementRepository $announcementRepository,
        LoggedUser $loggedUser,
        UpdateAnnouncementService $updateAnnouncement,
        Request $request,
    ): Response {
        $user = $loggedUser();

        $announcement = $this->getAnnouncementFromId($announcementId, $announcementRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $announcement);

        $form = $this->createForm(UpdateAnnouncementType::class, $this->updateRequestFrom($announcement));

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateAnnouncement, $user): void {
                $updateRequest = $form->getData();

                $this->mapManualUpload($form, $updateRequest);

                $updateAnnouncement($updateRequest, $user);

                $this->addFlash('success', new TranslatableMessage('Novedad actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_announcements');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@changelog/admin/announcements/edit.html.twig', [
            'announcement' => $announcement,
            'form' => $form->createView(),
        ]);
    }

    private function updateRequestFrom(Announcement $announcement): UpdateAnnouncementRequest
    {
        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;
        $translation = $announcement->translations()->getTranslationFor($locale);

        $manualFileName = $isDefaultLocale
            ? $announcement->manualFileName($locale)
            : ($translation instanceof AnnouncementTranslation ? $translation->manualFileName() : null);

        return new UpdateAnnouncementRequest(
            announcementId: $announcement->id(),
            category: $announcement->category(),
            date: $announcement->date(),
            version: $announcement->version(),
            title: $announcement->title(),
            summary: $announcement->summary(),
            body: $announcement->body(),
            youtubeUrl: $announcement->youtubeVideo()?->watchUrl(),
            manualFileName: $manualFileName,
        );
    }
}
