<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Waste;

use App\CarbonFootprint\Application\Waste\Update\EmissionFactorData;
use App\CarbonFootprint\Application\Waste\Update\UpdateWasteRequest;
use App\CarbonFootprint\Application\Waste\Update\UpdateWasteService;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateWasteType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/wastes/edit/{wasteId}', name: 'admin_wastes_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetWasteFromIdTrait;

    public function __invoke(
        string $wasteId,
        WasteRepository $wasteRepository,
        UpdateWasteService $updateWaste,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $waste = $this->getWasteFromId($wasteId, $wasteRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $waste);

        $emissionFactors = [];
        foreach ($waste->emissionFactors() as $wasteEmissionFactor) {
            $emissionFactors[] = EmissionFactorData::fromEmissionFactor($wasteEmissionFactor->emissionFactor());
        }

        $wasteRequest = new UpdateWasteRequest(
            wasteId: $waste->id(),
            name: $waste->name(),
            unit: $waste->unit(),
            density: $waste->density(),
            emissionFactors: $emissionFactors,
        );

        try {
            $form = $this->createForm(UpdateWasteType::class, $wasteRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateWaste, $loggedUser) {
                $wasteRequest = $form->getData();

                $updateWaste($wasteRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Residuo actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_wastes');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/wastes/edit.html.twig', [
            'waste' => $waste,
            'form' => $form->createView(),
        ]);
    }
}
