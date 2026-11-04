<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\DistributorAssignment;

use App\Property\Application\DistributorAssignment\List\ListDistributorAssignmentsRequest;
use App\Property\Application\DistributorAssignment\List\ListDistributorAssignmentsService;
use App\Property\Infrastructure\Symfony\Form\ListDistributorAssignmentsType;
use App\Property\Infrastructure\UI\Web\Admin\DistributorAssignment\Bulk\RemoveDistributorAssignment;
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
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/distributor-assignments', name: 'admin_distributor_assignments')]
#[IsGranted('ROLE_ADMIN')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListDistributorAssignmentsService $listDistributorAssignments, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            [$filter, $form] = $this->filter($request, fn(): ListDistributorAssignmentsRequest => new ListDistributorAssignmentsRequest(), ListDistributorAssignmentsType::class);

            $response = $listDistributorAssignments($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->assignments,
            'actions' => [
                new RemoveDistributorAssignment(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@property/admin/distributor_assignments/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'assignments' => $response->assignments,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
