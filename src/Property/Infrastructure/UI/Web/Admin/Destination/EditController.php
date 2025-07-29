<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Destination;

use App\Security\Application\Action;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Component\Translation\TranslatableMessage;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Property\Application\Destination\Update\UpdateDestinationRequest;
use App\Property\Application\Destination\Update\UpdateDestinationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Property\Infrastructure\Symfony\Form\AdminUpdateDestinationType;
use App\Property\Infrastructure\UI\Web\Admin\Destination\GetDestinationFromIdTrait;

#[Route('/admin/destinations/edit/{destinationId}', name: 'admin_destinations_edit')]
class EditController extends AbstractController
{
    use EditTrait, GetDestinationFromIdTrait;

    public function __invoke(
        string $destinationId, 
        DestinationRepository $destinationRepository, 
        UpdateDestinationService $updateDestination, 
        LoggedUser $loggedUser,
        Request $request
    ): Response
    {   
        $destination = $this->getDestinationFromId($destinationId, $destinationRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $destination);

        $destinationRequest = new UpdateDestinationRequest(
            destinationId: $destination->id(),
            name: $destination->name(),
            countryId: $destination->countryId(),
            externalId: $destination->externalId()
        );

        $form = $this->createForm(AdminUpdateDestinationType::class, $destinationRequest); 

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateDestination, $loggedUser) {
                $destinationRequest = $form->getData();

                $updateDestination($destinationRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Destino actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_destinations');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@property/admin/destinations/edit.html.twig', [
            'destination' => $destination,
            'form' => $form->createView()
        ]);
    }
}
