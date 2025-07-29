<?php

namespace App\Property\Infrastructure\UI\Web\Property;

use App\Security\Application\Action;
use App\Property\Domain\Model\Property\Property;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Property\Domain\Model\Property\PropertyId;
use Symfony\Component\Translation\TranslatableMessage;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Property\Infrastructure\Symfony\Form\RegisterPropertyType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Property\Application\Property\Register\RegisterPropertyService;

#[Route('/properties/register', name: 'properties_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        RegisterPropertyService $registerProperty, 
        Request $request
    ): Response
    {   
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException(
                new TranslatableMessage("Debes pertenecer a una cuenta para poder acceder")
            );
        }

        $this->denyAccessUnlessGranted(Action::Register->value, new Property(
            id: PropertyId::generate(),
            accountId: $account->id()
        ));

        $form = $this->createForm(RegisterPropertyType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerProperty, $account, $loggedUser) {
                $registerRequest = $form->getData();
                $registerRequest->accountId = $account->id();

                $response = $registerProperty($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage("Propiedad creada correctamente: {name}", ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('properties');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@property/properties/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
