<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Destination;

use App\Security\Application\Action;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Destination\Destination;
use Symfony\Component\Translation\TranslatableMessage;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Property\Infrastructure\Symfony\Form\AdminRegisterDestinationType;
use App\Property\Application\Destination\Register\RegisterDestinationRequest;
use App\Property\Application\Destination\Register\RegisterDestinationService;

#[Route('/admin/destinations/register', name: 'admin_destinations_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterDestinationService $registerDestination, LoggedUser $loggedUser, Request $request): Response
    {   
        $this->denyAccessUnlessGranted(Action::Register->value, new Destination(DestinationId::generate()));

        $registerRequest = new RegisterDestinationRequest(
            '', CountryId::generate()
        );

        $form = $this->createForm(AdminRegisterDestinationType::class, $registerRequest);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerDestination, $loggedUser) {
                $registerRequest = $form->getData();

                $response = $registerDestination($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage("Destino creado correctamente: {name}", ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_destinations');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@property/admin/destinations/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
