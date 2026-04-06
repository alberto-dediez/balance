<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Destination;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Application\Destination\Update\UpdateDestinationRequest;
use App\Property\Application\Destination\Update\UpdateDestinationService;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Property\Infrastructure\Symfony\Form\AdminUpdateDestinationType;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/destinations/edit/{destinationId}', name: 'admin_destinations_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetDestinationFromIdTrait;

    public function __invoke(
        string $destinationId,
        DestinationRepository $destinationRepository,
        UpdateDestinationService $updateDestination,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $destination = $this->getDestinationFromId($destinationId, $destinationRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $destination);

        assert($destination->countryId() instanceof CountryId);

        $destinationRequest = new UpdateDestinationRequest(
            destinationId: $destination->id(),
            name: $destination->name(),
            countryId: $destination->countryId(),
            externalId: $destination->externalId(),
        );

        $form = $this->createForm(AdminUpdateDestinationType::class, $destinationRequest);

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateDestination, $loggedUser): void {
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
            'form' => $form->createView(),
        ]);
    }
}
