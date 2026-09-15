<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\RefrigerantGas;

use App\CarbonFootprint\Application\RefrigerantGas\Update\EmissionFactorData;
use App\CarbonFootprint\Application\RefrigerantGas\Update\UpdateRefrigerantGasRequest;
use App\CarbonFootprint\Application\RefrigerantGas\Update\UpdateRefrigerantGasService;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateRefrigerantGasType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/refrigerant-gases/edit/{refrigerantGasId}', name: 'admin_refrigerant_gases_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetRefrigerantGasFromIdTrait;

    public function __invoke(
        string $refrigerantGasId,
        RefrigerantGasRepository $refrigerantGasRepository,
        UpdateRefrigerantGasService $updateRefrigerantGas,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $refrigerantGas = $this->getRefrigerantGasFromId($refrigerantGasId, $refrigerantGasRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $refrigerantGas);

        $emissionFactors = [];
        foreach ($refrigerantGas->emissionFactors() as $refrigerantGasEmissionFactor) {
            $emissionFactors[] = EmissionFactorData::fromEmissionFactor($refrigerantGasEmissionFactor->emissionFactor());
        }

        $refrigerantGasRequest = new UpdateRefrigerantGasRequest(
            refrigerantGasId: $refrigerantGas->id(),
            name: $refrigerantGas->name(),
            emissionFactors: $emissionFactors,
        );

        try {
            $form = $this->createForm(UpdateRefrigerantGasType::class, $refrigerantGasRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateRefrigerantGas, $loggedUser) {
                $refrigerantGasRequest = $form->getData();

                $updateRefrigerantGas($refrigerantGasRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Gas refrigerante actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_refrigerant_gases');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/refrigerant_gases/edit.html.twig', [
            'refrigerantGas' => $refrigerantGas,
            'form' => $form->createView(),
        ]);
    }
}
