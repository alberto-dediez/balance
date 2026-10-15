<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\UseProfile;

use App\CarbonFootprint\Application\UseProfile\List\ListUseProfilesRequest;
use App\CarbonFootprint\Application\UseProfile\List\ListUseProfilesService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListUseProfilesType;
use App\CarbonFootprint\Infrastructure\UI\Web\Admin\UseProfile\Bulk\RemoveAction;
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

#[Route('/admin/use-profiles', name: 'admin_use_profiles')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListUseProfilesService $listUseProfiles, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListUseProfilesRequest();
            }, ListUseProfilesType::class);

            $response = $listUseProfiles($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->useProfiles,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@carbon_footprint/admin/use_profiles/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'useProfiles' => $response->useProfiles,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
