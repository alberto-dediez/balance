<?php

declare(strict_types=1);

namespace App\HumanResources\Infrastructure\UI\Web\Admin\Employee;

use App\HumanResources\Application\Employee\List\ListEmployeesRequest;
use App\HumanResources\Application\Employee\List\ListEmployeesService;
use App\HumanResources\Infrastructure\Symfony\Form\AdminListEmployeesType;
use App\HumanResources\Infrastructure\UI\Web\Admin\Employee\Bulk\RemoveAction;
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

#[Route('/admin/employees', name: 'admin_employees')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListEmployeesService $listEmployees, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListEmployeesRequest();
            }, AdminListEmployeesType::class);

            $response = $listEmployees($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->employees,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@human_resources/admin/employees/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'employees' => $response->employees,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
