<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\PurchaseService;

use App\CarbonFootprint\Application\PurchaseService\Register\RegisterPurchaseServiceService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterPurchaseServiceType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/purchase-services/register', name: 'admin_purchase_services_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterPurchaseServiceService $registerPurchaseService, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new PurchaseService(PurchaseServiceId::generate()));

        $form = $this->createForm(RegisterPurchaseServiceType::class);

        try {
            $purchaseServiceId = null;

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerPurchaseService, $loggedUser, &$purchaseServiceId) {
                $registerRequest = $form->getData();

                $response = $registerPurchaseService($registerRequest, $loggedUser());

                $purchaseServiceId = $response->purchaseServiceId;

                $this->addFlash('success', new TranslatableMessage('Bien o servicio comprado creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                if ($purchaseServiceId instanceof PurchaseServiceId) {
                    return $this->redirectToRoute('admin_purchase_services_edit', ['purchaseServiceId' => $purchaseServiceId]);
                }

                return $this->redirectToRoute('admin_purchase_services');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/purchase_services/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
