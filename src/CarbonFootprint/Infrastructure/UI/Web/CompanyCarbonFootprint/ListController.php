<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\List\ListCompanyCarbonFootprintsRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\List\ListCompanyCarbonFootprintsService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListCompanyCarbonFootprintsType;
use App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint\Bulk\RemoveCompanyCarbonFootprint;
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

#[Route('/company-carbon-footprints', name: 'company_carbon_footprints')]
class ListController extends AbstractController
{
    use BulkTrait;
    use FilterTrait;

    public function __invoke(
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        ListCompanyCarbonFootprintsService $listCompanyCarbonFootprints,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $account = $loggedAccount();

        if (null === $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        try {
            [$filter, $form] = $this->filter($request, fn(): ListCompanyCarbonFootprintsRequest => new ListCompanyCarbonFootprintsRequest(
                accountId: $account->id(),
            ), ListCompanyCarbonFootprintsType::class);

            $response = $listCompanyCarbonFootprints($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->companyCarbonFootprints,
            'actions' => [
                new RemoveCompanyCarbonFootprint(),
            ],
        ]);

        if ($this->bulkActions($request, $bulkForm, $loggedUser())) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@carbon_footprint/company_carbon_footprints/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'companyCarbonFootprints' => $response->companyCarbonFootprints,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
