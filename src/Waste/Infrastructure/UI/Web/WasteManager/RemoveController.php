<?php

namespace App\Waste\Infrastructure\UI\Web\WasteManager;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Application\WasteManager\Remove\RemoveWasteManagerRequest;
use App\Waste\Application\WasteManager\Remove\RemoveWasteManagerService;
use App\Waste\Infrastructure\UI\Web\WasteManager\GetWasteManagerFromIdTrait;

#[Route('/waste-managers/remove/{wasteManagerId}', name: 'waste_managers_remove')]
class RemoveController extends AbstractController
{
    use GetWasteManagerFromIdTrait;

    public function __invoke(
        string $wasteManagerId, 
        WasteManagerRepository $wasteManagerRepository,
        LoggedUser $loggedUser,
        RemoveWasteManagerService $removeWasteManager
    ): Response
    {   
        $wasteManager = $this->getWasteManagerFromId($wasteManagerId, $wasteManagerRepository);

        try {
            $removeWasteManager(new RemoveWasteManagerRequest($wasteManager->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage("Se ha eliminado el gestor de residuos {wasteManager}", ['{wasteManager}' => $wasteManager->name()]));
        
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage("No se ha podido eliminar el gestor de residuos {wasteManager}", ['{wasteManager}' => $wasteManager->name()]));
        }

        return $this->redirectToRoute('waste_managers');
    }
}
