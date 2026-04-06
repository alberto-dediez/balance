<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DeliveryLog;

use App\FoodLoss\Application\DeliveryLog\Register\RegisterDeliveryLogRequest;
use App\FoodLoss\Application\DeliveryLog\Register\RegisterDeliveryLogService;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLog;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Infrastructure\Symfony\Form\RegisterDeliveryLogType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/delivery-logs/register', name: 'delivery_logs_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterDeliveryLogService $registerDeliveryLog, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new DeliveryLog(DeliveryLogId::generate(), accountId: $loggedAccount()?->id()));

        $form = $this->createForm(RegisterDeliveryLogType::class, new RegisterDeliveryLogRequest());

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerDeliveryLog, $loggedUser, $loggedAccount): true {
                $registerRequest = $form->getData();
                $registerRequest->accountId = $loggedAccount()?->id();

                $response = $registerDeliveryLog($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Registro de entrega creado correctamente: {productType}', ['{productType}' => $response->productType]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('delivery_logs');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/delivery-logs/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
