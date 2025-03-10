<?php

namespace App\Waste\Infrastructure\UI\Web\Property;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Waste\Infrastructure\Symfony\Form\RegisterPropertyType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Application\Property\Register\RegisterPropertyService;

#[Route('/properties/register', name: 'properties_register')]
#[IsGranted("ROLE_OWNER")]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
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

        $form = $this->createForm(RegisterPropertyType::class);

        $needsRedirect = $this->register($request, $form, function ($form) use ($registerProperty, $account) {
            $registerRequest = $form->getData();
            $registerRequest->account = $account;

            $response = $registerProperty($registerRequest);

            $this->addFlash('success', new TranslatableMessage("Propiedad creada correctamente: {name}", ['{name}' => $response->name]));

            return true;
        });

        if ($needsRedirect) {
            return $this->redirectToRoute('properties');
        }

        return $this->render('@waste/properties/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
