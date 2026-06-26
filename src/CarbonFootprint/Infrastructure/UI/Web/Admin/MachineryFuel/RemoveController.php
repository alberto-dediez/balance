<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MachineryFuel;

use App\CarbonFootprint\Application\MachineryFuel\Remove\RemoveMachineryFuelRequest;
use App\CarbonFootprint\Application\MachineryFuel\Remove\RemoveMachineryFuelService;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/machinery-fuels/remove/{machineryFuelId}', name: 'admin_machinery_fuels_remove')]
class RemoveController extends AbstractController
{
    use GetMachineryFuelFromIdTrait;

    public function __invoke(
        string $machineryFuelId,
        MachineryFuelRepository $machineryFuelRepository,
        RemoveMachineryFuelService $removeMachineryFuel,
        LoggedUser $loggedUser,
    ): Response {
        $machineryFuel = $this->getMachineryFuelFromId($machineryFuelId, $machineryFuelRepository);

        try {
            $removeMachineryFuel(new RemoveMachineryFuelRequest($machineryFuel->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el combustible de maquinaria {name}', ['{name}' => $machineryFuel->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el combustible de maquinaria {name}', ['{name}' => $machineryFuel->name()]));
        }

        return $this->redirectToRoute('admin_machinery_fuels');
    }
}
