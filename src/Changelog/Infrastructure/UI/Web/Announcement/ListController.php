<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\Announcement;

use App\Changelog\Application\Announcement\List\ListAnnouncementsRequest;
use App\Changelog\Application\Announcement\List\ListAnnouncementsService;
use App\Changelog\Domain\Model\Announcement\AnnouncementStatus;
use App\Changelog\Infrastructure\Symfony\Form\ListUserAnnouncementsType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/announcements', name: 'announcements')]
#[IsGranted('ROLE_USER')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListAnnouncementsService $listAnnouncements, LoggedUser $loggedUser, Request $request): Response
    {
        [$filter, $form] = $this->filter($request, fn(): ListAnnouncementsRequest => new ListAnnouncementsRequest(status: AnnouncementStatus::Published), ListUserAnnouncementsType::class);

        try {
            $response = $listAnnouncements($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@changelog/announcements/list.html.twig', [
            'announcements' => $response->announcements,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
