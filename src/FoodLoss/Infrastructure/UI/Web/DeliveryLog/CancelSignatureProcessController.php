<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DeliveryLog;

use App\FoodLoss\Application\DeliveryLog\CancelSignatureProcess\CancelSignatureProcessRequest;
use App\FoodLoss\Application\DeliveryLog\CancelSignatureProcess\CancelSignatureProcessService;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\CancelSignatureProcessType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/delivery-logs/cancel-signature-process/{signatureRequestId}', name: 'delivery_logs_cancel_signature_process')]
class CancelSignatureProcessController extends AbstractController
{
    public function __invoke(
        string $signatureRequestId,
        DeliveryLogRepository $repository,
        CancelSignatureProcessService $cancelSignatureProcess,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $signatureId = new SignatureRequestId($signatureRequestId);

        $deliveryLogs = $repository->findBySignatureRequestId($signatureId);

        if ([] === $deliveryLogs) {
            throw $this->createNotFoundException('No se encontraron registros de entrega para esta solicitud de firma');
        }

        $form = $this->createForm(CancelSignatureProcessType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $cancelSignatureProcess(new CancelSignatureProcessRequest($signatureId), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Se ha cancelado el proceso de firma correctamente'));
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (\Exception) {
                $this->addFlash('danger', new TranslatableMessage('No se ha podido cancelar el proceso de firma'));
            }

            return $this->redirectToRoute('delivery_logs');
        }

        return $this->render('@food_loss/delivery-logs/cancel-signature-process.html.twig', [
            'deliveryLogs' => $deliveryLogs,
            'form' => $form->createView(),
        ]);
    }
}
