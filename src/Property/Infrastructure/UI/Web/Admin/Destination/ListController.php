<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Destination;

use App\Security\Application\Action;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Property\Domain\Model\Destination\Destination;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Property\Application\Destination\List\ListDestinationsRequest;
use App\Property\Application\Destination\List\ListDestinationsService;
use App\Property\Infrastructure\Symfony\Form\AdminListDestinationsType;
use App\Property\Infrastructure\UI\Web\Admin\Destination\Bulk\RemoveDestination;

#[Route('/admin/destinations', name: 'admin_destinations')]
class ListController extends AbstractController
{
    use FilterTrait, BulkTrait;

    public function __invoke(ListDestinationsService $listDestinations, LoggedUser $loggedUser, Request $request): Response
    {   
        $this->denyAccessUnlessGranted(Action::List->value, new Destination(DestinationId::generate()));

        try {
            list($filter, $form) = $this->filter($request, function () {
                return  new ListDestinationsRequest();
            }, AdminListDestinationsType::class);

            $response = $listDestinations($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest, [
            'elements' => $response->destinations,
            'actions' => [
                new RemoveDestination()
            ]
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@property/admin/destinations/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'destinations' => $response->destinations,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
