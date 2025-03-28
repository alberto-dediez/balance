<?php

namespace App\Waste\Infrastructure\UI\Web\Container;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Waste\Infrastructure\Symfony\Form\RegisterContainerType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Application\Container\Register\RegisterContainerRequest;
use App\Waste\Application\Container\Register\RegisterContainerService;

#[Route('/containers/register', name: 'containers_register')]
#[IsGranted("ROLE_OWNER")]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        LoggedAccount $loggedAccount,
        RegisterContainerService $registerContainer, 
        Request $request
    ): Response
    {   
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException(
                new TranslatableMessage("Debes pertenecer a una cuenta para poder acceder")
            );
        }

        $form = $this->createForm(RegisterContainerType::class);

        $needsRedirect = $this->register($request, $form, function ($form) use ($registerContainer) {
            $registerRequest = new RegisterContainerRequest(
                propertyId: $form->get('propertyId')->getData(),                
                wasteId: $form->get('wasteId')->getData(),                
                purpose: $form->get('purpose')->getData(),                
                measurementMethod: $form->get('measurementMethod')->getData(),                
            );

            $response = $registerContainer($registerRequest);

            $this->addFlash('success', new TranslatableMessage("Contenedor creado correctamente"));

            return true;
        });

        if ($needsRedirect) {
            return $this->redirectToRoute('containers');
        }

        return $this->render('@waste/containers/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
