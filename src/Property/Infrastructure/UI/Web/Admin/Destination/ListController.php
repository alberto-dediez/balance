<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Destination;

use App\Security\Application\Action;
use App\Property\Domain\Model\Destination\Destination;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Property\Application\Destination\List\ListDestinationsRequest;
use App\Property\Application\Destination\List\ListDestinationsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Property\Infrastructure\Symfony\Form\AdminListDestinationsType;

#[Route('/admin/destinations', name: 'admin_destinations')]
class ListController extends AbstractController
{
    use FilterTrait;

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

        return $this->render('@property/admin/destinations/list.html.twig', [
            'destinations' => $response->destinations,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
