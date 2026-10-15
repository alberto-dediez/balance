<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\PurchaseService;

use App\CarbonFootprint\Application\PurchaseService\Update\EmissionFactorData;
use App\CarbonFootprint\Application\PurchaseService\Update\UpdatePurchaseServiceRequest;
use App\CarbonFootprint\Application\PurchaseService\Update\UpdatePurchaseServiceService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdatePurchaseServiceType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/purchase-services/edit/{purchaseServiceId}', name: 'admin_purchase_services_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetPurchaseServiceFromIdTrait;

    public function __invoke(
        string $purchaseServiceId,
        PurchaseServiceRepository $purchaseServiceRepository,
        UpdatePurchaseServiceService $updatePurchaseService,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $purchaseService = $this->getPurchaseServiceFromId($purchaseServiceId, $purchaseServiceRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $purchaseService);

        $emissionFactors = [];
        foreach ($purchaseService->emissionFactors() as $purchaseServiceEmissionFactor) {
            $emissionFactors[] = EmissionFactorData::fromPurchaseServiceEmissionFactor($purchaseServiceEmissionFactor);
        }

        $purchaseServiceRequest = new UpdatePurchaseServiceRequest(
            purchaseServiceId: $purchaseService->id(),
            name: $purchaseService->name(),
            category: $purchaseService->category(),
            unit: $purchaseService->unit(),
            emissionFactors: $emissionFactors,
        );

        try {
            $form = $this->createForm(UpdatePurchaseServiceType::class, $purchaseServiceRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updatePurchaseService, $loggedUser) {
                $purchaseServiceRequest = $form->getData();

                $updatePurchaseService($purchaseServiceRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Bien o servicio comprado actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_purchase_services');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/purchase_services/edit.html.twig', [
            'purchaseService' => $purchaseService,
            'form' => $form->createView(),
        ]);
    }
}
