<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\Project;

use App\Balance\Application\Project\List\ListProjectsRequest;
use App\Balance\Application\Project\List\ListProjectsService;
use App\Balance\Infrastructure\Symfony\Form\ListProjectsType;
use App\Balance\Infrastructure\UI\Web\Admin\Project\Bulk\RemoveAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/projects', name: 'admin_projects')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListProjectsService $listProjects, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListProjectsRequest();
            }, ListProjectsType::class);

            $response = $listProjects($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->projects,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@balance/admin/projects/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'projects' => $response->projects,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
