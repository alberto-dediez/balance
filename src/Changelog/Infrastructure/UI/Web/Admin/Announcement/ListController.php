<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\Admin\Announcement;

use App\Changelog\Application\Announcement\List\ListAnnouncementsRequest;
use App\Changelog\Application\Announcement\List\ListAnnouncementsService;
use App\Changelog\Infrastructure\Symfony\Form\ListAnnouncementsType;
use App\Changelog\Infrastructure\UI\Web\Admin\Announcement\Bulk\RemoveAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/announcements', name: 'admin_announcements')]
#[IsGranted('ROLE_ADMIN')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListAnnouncementsService $listAnnouncements, LoggedUser $loggedUser, Request $request): Response
    {
        [$filter, $form] = $this->filter($request, fn(): ListAnnouncementsRequest => new ListAnnouncementsRequest(), ListAnnouncementsType::class);

        try {
            $response = $listAnnouncements($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->announcements,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@changelog/admin/announcements/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'announcements' => $response->announcements,
            'totalResults' => $response->totalResults,
            'defaultLocale' => LocaleGetter::default(),
            'form' => $form->createView(),
        ]);
    }
}
