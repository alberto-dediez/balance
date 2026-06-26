<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MachineryFuel;

use App\CarbonFootprint\Application\MachineryFuel\Update\EmissionFactorData;
use App\CarbonFootprint\Application\MachineryFuel\Update\UpdateMachineryFuelRequest;
use App\CarbonFootprint\Application\MachineryFuel\Update\UpdateMachineryFuelService;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateMachineryFuelType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/machinery-fuels/edit/{machineryFuelId}', name: 'admin_machinery_fuels_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetMachineryFuelFromIdTrait;

    public function __invoke(
        string $machineryFuelId,
        MachineryFuelRepository $machineryFuelRepository,
        UpdateMachineryFuelService $updateMachineryFuel,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $machineryFuel = $this->getMachineryFuelFromId($machineryFuelId, $machineryFuelRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $machineryFuel);

        $emissionFactors = [];
        foreach ($machineryFuel->emissionFactors() as $machineryFuelEmissionFactor) {
            $emissionFactors[] = EmissionFactorData::fromEmissionFactor($machineryFuelEmissionFactor->emissionFactor());
        }

        $machineryFuelRequest = new UpdateMachineryFuelRequest(
            machineryFuelId: $machineryFuel->id(),
            name: $machineryFuel->name(),
            unit: $machineryFuel->unit(),
            machineryType: $machineryFuel->machineryType(),
            emissionFactors: $emissionFactors,
        );

        try {
            $form = $this->createForm(UpdateMachineryFuelType::class, $machineryFuelRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateMachineryFuel, $loggedUser) {
                $machineryFuelRequest = $form->getData();

                $updateMachineryFuel($machineryFuelRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Combustible de maquinaria actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_machinery_fuels');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/machinery_fuels/edit.html.twig', [
            'machineryFuel' => $machineryFuel,
            'form' => $form->createView(),
        ]);
    }
}
