<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Company;

use App\Property\Application\Company\List\ListCompaniesRequest;
use App\Property\Application\Company\List\ListCompaniesService;
use App\Property\Infrastructure\Symfony\Form\ListCompaniesType;
use App\Property\Infrastructure\UI\Web\Company\Bulk\RemoveAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/companies', name: 'companies')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(
        ListCompaniesService $listCompanies,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        try {
            [$filter, $form] = $this->filter($request, function () use ($account): ListCompaniesRequest {
                $request = new ListCompaniesRequest();
                $request->accountId = $account->id();

                return $request;
            }, ListCompaniesType::class);

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

        return $this->render('@property/companies/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'companies' => $response->companies,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
