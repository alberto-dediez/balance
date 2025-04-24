<?php

namespace App\Waste\Infrastructure\UI\Web\Admin\Property;

use App\Security\Application\Action;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Component\Translation\TranslatableMessage;
use App\Waste\Domain\Model\Property\PropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Application\Property\Update\UpdatePropertyRequest;
use App\Waste\Application\Property\Update\UpdatePropertyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Infrastructure\Symfony\Form\AdminUpdatePropertyType;
use App\Waste\Infrastructure\UI\Web\Admin\Property\GetPropertyFromIdTrait;

#[Route('/admin/properties/edit/{propertyId}', name: 'admin_properties_edit')]
class EditController extends AbstractController
{
    use EditTrait, GetPropertyFromIdTrait;

    public function __invoke(
        string $propertyId, 
        PropertyRepository $propertyRepository, 
        UpdatePropertyService $updateProperty, 
        LoggedUser $loggedUser,
        Request $request
    ): Response
    {   
        $property = $this->getPropertyFromId($propertyId, $propertyRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $property);
        $this->denyAccessUnlessGranted(Action::Admin->value, $property);

        $propertyRequest = new UpdatePropertyRequest(
            propertyId: $property->id(),
            name: $property->name(),
            accountId: $property->accountId()
        );

        $form = $this->createForm(AdminUpdatePropertyType::class, $propertyRequest); 

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateProperty, $loggedUser) {
                $propertyRequest = $form->getData();

                $updateProperty($propertyRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Propiedad actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_properties');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@waste/admin/properties/edit.html.twig', [
            'property' => $property,
            'form' => $form->createView()
        ]);
    }
}
