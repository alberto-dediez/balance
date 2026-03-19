<?php

namespace App\Waste\Infrastructure\UI\Web\WasteManager;

use App\Security\Application\Action;
use App\Waste\Domain\Model\WasteManager\WasteManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use Symfony\Component\Translation\TranslatableMessage;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Waste\Infrastructure\Symfony\Form\RegisterWasteManagerType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Application\WasteManager\Register\RegisterWasteManagerService;

#[Route('/waste-managers/register', name: 'waste_managers_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        RegisterWasteManagerService $registerWasteManager, 
        Request $request
    ): Response
    {   
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException(
                new TranslatableMessage("Debes pertenecer a una cuenta para poder acceder")
            );
        }

        $this->denyAccessUnlessGranted(Action::Register->value, new WasteManager(
            id: WasteManagerId::generate(),
            accountId: $account->id()
        ));

        $form = $this->createForm(RegisterWasteManagerType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerWasteManager, $account, $loggedUser): true {
                $registerRequest = $form->getData();
                $registerRequest->accountId = $account->id();

                $response = $registerWasteManager($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage("Gestor de residuos creado correctamente: {name}", ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('waste_managers');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@waste/waste-managers/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
