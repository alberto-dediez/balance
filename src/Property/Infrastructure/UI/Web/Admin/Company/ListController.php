<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Company;

use App\Property\Application\Company\List\ListCompaniesRequest;
use App\Property\Application\Company\List\ListCompaniesService;
use App\Property\Infrastructure\Symfony\Form\AdminListCompaniesType;
use App\Property\Infrastructure\UI\Web\Admin\Company\Bulk\RemoveAction;
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

#[Route('/admin/companies', name: 'admin_companies')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListCompaniesService $listCompanies, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            [$filter, $form] = $this->filter($request, function (): ListCompaniesRequest {
                return new ListCompaniesRequest();
            }, AdminListCompaniesType::class);

            $response = $listCompanies($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->companies,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@property/admin/companies/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'companies' => $response->companies,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
