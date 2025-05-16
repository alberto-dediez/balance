<?php

namespace App\Waste\Infrastructure\UI\Web\Property;

use App\Security\Application\Action;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Component\Translation\TranslatableMessage;
use App\Waste\Domain\Model\Property\PropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Infrastructure\Symfony\Form\UpdatePropertyType;
use App\Waste\Application\Property\Update\UpdatePropertyRequest;
use App\Waste\Application\Property\Update\UpdatePropertyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Infrastructure\UI\Web\Admin\Property\GetPropertyFromIdTrait;

#[Route('/properties/edit/{propertyId}', name: 'properties_edit')]
class EditController extends AbstractController
{
    use EditTrait, GetPropertyFromIdTrait;

    public function __invoke(
        string $propertyId, 
        LoggedUser $loggedUser,
        PropertyRepository $propertyRepository, 
        UpdatePropertyService $updateProperty, 
        Request $request
    ): Response
    {   
        $property = $this->getPropertyFromId($propertyId, $propertyRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $property);

        $propertyRequest = new UpdatePropertyRequest(
            propertyId: $property->id(),
            name: $property->name(),
            accountId: $property->accountId()
        );

        $form = $this->createForm(UpdatePropertyType::class, $propertyRequest); 

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateProperty, $loggedUser) {
                $propertyRequest = $form->getData();

                $updateProperty($propertyRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Propiedad actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('properties');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@waste/properties/edit.html.twig', [
            'property' => $property,
            'form' => $form->createView()
        ]);
    }
}
