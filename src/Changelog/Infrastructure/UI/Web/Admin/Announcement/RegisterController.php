<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\Admin\Announcement;

use App\Changelog\Application\Announcement\Register\RegisterAnnouncementRequest;
use App\Changelog\Application\Announcement\Register\RegisterAnnouncementService;
use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Infrastructure\Symfony\Form\RegisterAnnouncementType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/announcements/register', name: 'admin_announcements_register')]
#[IsGranted('ROLE_ADMIN')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterAnnouncementService $registerAnnouncement, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new Announcement(AnnouncementId::generate()));

        $user = $loggedUser();

        $form = $this->createForm(RegisterAnnouncementType::class, new RegisterAnnouncementRequest());

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerAnnouncement, $user): true {
                $registerRequest = $form->getData();

                $response = $registerAnnouncement($registerRequest, $user);

                $this->addFlash('success', new TranslatableMessage('Novedad registrada correctamente: {title}', ['{title}' => $response->title]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_announcements');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@changelog/admin/announcements/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
