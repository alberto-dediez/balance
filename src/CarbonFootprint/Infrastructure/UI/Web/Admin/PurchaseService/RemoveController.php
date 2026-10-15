<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\PurchaseService;

use App\CarbonFootprint\Application\PurchaseService\Remove\RemovePurchaseServiceRequest;
use App\CarbonFootprint\Application\PurchaseService\Remove\RemovePurchaseServiceService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/purchase-services/remove/{purchaseServiceId}', name: 'admin_purchase_services_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetPurchaseServiceFromIdTrait;

    public function __invoke(
        string $purchaseServiceId,
        PurchaseServiceRepository $purchaseServiceRepository,
        RemovePurchaseServiceService $removePurchaseService,
        LoggedUser $loggedUser,
    ): Response {
        $purchaseService = $this->getPurchaseServiceFromId($purchaseServiceId, $purchaseServiceRepository);

        try {
            $removePurchaseService(new RemovePurchaseServiceRequest($purchaseService->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el bien o servicio comprado {purchaseService}', ['{purchaseService}' => $purchaseService->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el bien o servicio comprado {purchaseService}', ['{purchaseService}' => $purchaseService->name()]));
        }

        return $this->redirectToRoute('admin_purchase_services');
    }
}
