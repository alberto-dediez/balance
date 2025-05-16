<?php

namespace App\Waste\Infrastructure\UI\Web\Container;

use App\Security\Application\Action;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerId;
use Symfony\Component\Translation\TranslatableMessage;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Waste\Infrastructure\Symfony\Form\RegisterContainerType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Application\Container\Register\RegisterContainerRequest;
use App\Waste\Application\Container\Register\RegisterContainerService;

#[Route('/containers/register', name: 'containers_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        LoggedAccount $loggedAccount,
        LoggedUser $loggedUser,
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

        $this->denyAccessUnlessGranted(Action::Register->value, new Container(
            id: ContainerId::generate(),
            accountId: $account->id()
        ));

        try {
            $form = $this->createForm(RegisterContainerType::class);

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerContainer, $loggedUser) {
                $registerRequest = new RegisterContainerRequest(
                    propertyId: $form->get('propertyId')->getData(),                
                    wasteId: $form->get('wasteId')->getData(),                
                    purpose: $form->get('purpose')->getData(),                
                    measurementMethod: $form->get('measurementMethod')->getData(),         
                    wasteManagerId: $form->get('wasteManagerId')->getData()       
                );

                $response = $registerContainer($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage("Contenedor creado correctamente"));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('containers');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@waste/containers/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
