<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DeliveryLog;

use App\FoodLoss\Application\DeliveryLog\Remove\RemoveDeliveryLogRequest;
use App\FoodLoss\Application\DeliveryLog\Remove\RemoveDeliveryLogService;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/delivery-logs/remove/{deliveryLogId}', name: 'delivery_logs_remove')]
class RemoveController extends AbstractController
{
    use GetDeliveryLogFromIdTrait;

    public function __invoke(
        string $deliveryLogId,
        DeliveryLogRepository $repository,
        RemoveDeliveryLogService $removeDeliveryLog,
        LoggedUser $loggedUser,
    ): Response {
        $deliveryLog = $this->getDeliveryLogFromId($deliveryLogId, $repository);

        try {
            $removeDeliveryLog(new RemoveDeliveryLogRequest($deliveryLog->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el registro de entrega de {productType}', ['{productType}' => $deliveryLog->productType()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el registro de entrega'));
        }

        return $this->redirectToRoute('delivery_logs');
    }
}
