<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\List\ListPropertyCarbonFootprintsRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\List\ListPropertyCarbonFootprintsService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListPropertyCarbonFootprintsType;
use App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint\Bulk\RemovePropertyCarbonFootprint;
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

#[Route('/property-carbon-footprints', name: 'property_carbon_footprints')]
class ListController extends AbstractController
{
    use BulkTrait;
    use FilterTrait;

    public function __invoke(
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        ListPropertyCarbonFootprintsService $listPropertyCarbonFootprints,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $account = $loggedAccount();

        if (null === $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        try {
            [$filter, $form] = $this->filter($request, fn(): ListPropertyCarbonFootprintsRequest => new ListPropertyCarbonFootprintsRequest(
                accountId: $account->id(),
            ), ListPropertyCarbonFootprintsType::class);

            $response = $listPropertyCarbonFootprints($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->propertyCarbonFootprints,
            'actions' => [
                new RemovePropertyCarbonFootprint(),
            ],
        ]);

        if ($this->bulkActions($request, $bulkForm, $loggedUser())) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@carbon_footprint/property_carbon_footprints/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'propertyCarbonFootprints' => $response->propertyCarbonFootprints,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
