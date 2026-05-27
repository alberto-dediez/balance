<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\WasteManager;

use App\FoodLoss\Application\WasteManager\Register\RegisterWasteManagerRequest;
use App\FoodLoss\Application\WasteManager\Register\RegisterWasteManagerService;
use App\FoodLoss\Domain\Model\WasteManager\WasteManager;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerId;
use App\FoodLoss\Infrastructure\Symfony\Form\RegisterWasteManagerType;
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

#[Route('/food-waste-managers/register', name: 'food_waste_managers_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterWasteManagerService $registerWasteManager, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new WasteManager(
            id: WasteManagerId::generate(),
            accountId: $loggedAccount()?->id(),
        ));

        $form = $this->createForm(RegisterWasteManagerType::class, new RegisterWasteManagerRequest());

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerWasteManager, $loggedUser, $loggedAccount): true {
                $registerRequest = $form->getData();
                $registerRequest->accountId = $loggedAccount()?->id();

                $response = $registerWasteManager($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Gestor de residuos creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('food_waste_managers');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/waste-managers/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
