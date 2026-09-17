<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Destination;

use App\Property\Application\Destination\Remove\RemoveDestinationRequest;
use App\Property\Application\Destination\Remove\RemoveDestinationService;
use App\Property\Domain\Model\Destination\Destination;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/destinations/remove/{destinationId}', name: 'admin_destinations_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetDestinationFromIdTrait;

    public function __invoke(
        string $destinationId,
        DestinationRepository $destinationRepository,
        RemoveDestinationService $removeDestination,
        LoggedUser $loggedUser,
    ): Response {
        $this->denyAccessUnlessGranted(Action::Admin->value, new Destination(DestinationId::generate()));

        $destination = $this->getDestinationFromId($destinationId, $destinationRepository);

        try {
            $removeDestination(new RemoveDestinationRequest($destination->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el destino {destination}', ['{destination}' => $destination->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el destino {destination}', ['{destination}' => $destination->name()]));
        }

        return $this->redirectToRoute('admin_destinations');
    }
}
