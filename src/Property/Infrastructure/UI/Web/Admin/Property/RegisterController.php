<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Property;

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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Property\Application\Property\Register\RegisterPropertyService;
use App\Property\Infrastructure\Symfony\Form\AdminRegisterPropertyType;

#[Route('/admin/properties/register', name: 'admin_properties_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterPropertyService $registerProperty, LoggedUser $loggedUser, Request $request): Response
    {   
        $this->denyAccessUnlessGranted(Action::Register->value, new Property(PropertyId::generate()));
        $this->denyAccessUnlessGranted(Action::Admin->value, new Property(PropertyId::generate()));

        $form = $this->createForm(AdminRegisterPropertyType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerProperty, $loggedUser) {
                $registerRequest = $form->getData();

                $response = $registerProperty($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage("Propiedad creada correctamente: {name}", ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_properties');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@property/admin/properties/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
