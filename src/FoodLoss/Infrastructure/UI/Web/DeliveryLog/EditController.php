<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DeliveryLog;

use App\FoodLoss\Application\DeliveryLog\Update\UpdateDeliveryLogRequest;
use App\FoodLoss\Application\DeliveryLog\Update\UpdateDeliveryLogService;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdateDeliveryLogType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/delivery-logs/edit/{deliveryLogId}', name: 'delivery_logs_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetDeliveryLogFromIdTrait;

    public function __invoke(
        string $deliveryLogId,
        DeliveryLogRepository $repository,
        UpdateDeliveryLogService $updateDeliveryLog,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $deliveryLog = $this->getDeliveryLogFromId($deliveryLogId, $repository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $deliveryLog);

        $updateRequest = new UpdateDeliveryLogRequest(
            deliveryLogId: $deliveryLog->id(),
            date: $deliveryLog->date(),
            quantity: $deliveryLog->quantity(),
            unit: $deliveryLog->unit(),
            productType: $deliveryLog->productType(),
            batch: $deliveryLog->batch(),
            recipientOrganizationId: $deliveryLog->recipientOrganizationId(),
            propertyId: $deliveryLog->propertyId(),
        );

        try {
            $form = $this->createForm(UpdateDeliveryLogType::class, $updateRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateDeliveryLog, $loggedUser) {
                $updateRequest = $form->getData();

                $updateDeliveryLog($updateRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Registro de entrega actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('delivery_logs');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/delivery-logs/edit.html.twig', [
            'deliveryLog' => $deliveryLog,
            'form' => $form->createView(),
        ]);
    }
}
