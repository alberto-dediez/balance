<?php

namespace App\Waste\Infrastructure\UI\Web\Property;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Component\Translation\TranslatableMessage;
use App\Waste\Domain\Model\Property\PropertyRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Waste\Infrastructure\Symfony\Form\UpdatePropertyType;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Waste\Application\Property\Update\UpdatePropertyRequest;
use App\Waste\Application\Property\Update\UpdatePropertyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Infrastructure\UI\Web\Admin\Property\GetPropertyFromIdTrait;

#[Route('/properties/edit/{propertyId}', name: 'properties_edit')]
#[IsGranted("ROLE_OWNER")]
class EditController extends AbstractController
{
    use EditTrait, GetPropertyFromIdTrait, OnlyPropertiesFromAccountTrait;

    public function __invoke(
        string $propertyId, 
        LoggedAccount $loggedAccount,
        PropertyRepository $propertyRepository, 
        UpdatePropertyService $updateProperty, 
        Request $request
    ): Response
    {   
        $property = $this->getPropertyFromId($propertyId, $propertyRepository);

        $this->onlyPropertiesFromAccount($property, $loggedAccount());

        $propertyRequest = new UpdatePropertyRequest(
            propertyId: $property->id(),
            name: $property->name(),
            account: $property->account()
        );

        $form = $this->createForm(UpdatePropertyType::class, $propertyRequest); 

        $needsRedirect = $this->edit($request, $form, function ($form) use ($updateProperty) {
            $propertyRequest = $form->getData();

            $updateProperty($propertyRequest);

            $this->addFlash('success', new TranslatableMessage('Propiedad actualizada con éxito'));
        });

        if ($needsRedirect) {
            return $this->redirectToRoute('properties');
        }

        return $this->render('@waste/properties/edit.html.twig', [
            'property' => $property,
            'form' => $form->createView()
        ]);
    }
}
