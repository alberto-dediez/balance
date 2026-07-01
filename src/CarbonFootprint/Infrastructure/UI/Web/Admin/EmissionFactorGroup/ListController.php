<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\EmissionFactorGroup;

use App\CarbonFootprint\Application\EmissionFactorGroup\List\ListEmissionFactorGroupsRequest;
use App\CarbonFootprint\Application\EmissionFactorGroup\List\ListEmissionFactorGroupsService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListEmissionFactorGroupsType;
use App\CarbonFootprint\Infrastructure\UI\Web\Admin\EmissionFactorGroup\Bulk\RemoveAction;
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

#[Route('/admin/emission-factor-groups', name: 'admin_emission_factor_groups')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListEmissionFactorGroupsService $listEmissionFactorGroups, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListEmissionFactorGroupsRequest();
            }, ListEmissionFactorGroupsType::class);

            $response = $listEmissionFactorGroups($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->emissionFactorGroups,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@carbon_footprint/admin/emission_factor_groups/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'emissionFactorGroups' => $response->emissionFactorGroups,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
