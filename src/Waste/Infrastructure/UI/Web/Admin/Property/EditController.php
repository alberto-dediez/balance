<?php

namespace App\Waste\Infrastructure\UI\Web\Admin\Property;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Component\Translation\TranslatableMessage;
use App\Waste\Domain\Model\Property\PropertyRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Waste\Infrastructure\Symfony\Form\UpdateProperty;
use App\Waste\Infrastructure\Symfony\Form\AdminUpdatePropertyType;
use App\Waste\Application\Property\Update\UpdatePropertyRequest;
use App\Waste\Application\Property\Update\UpdatePropertyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Infrastructure\UI\Web\Admin\Property\GetPropertyFromIdTrait;

#[Route('/admin/properties/edit/{propertyId}', name: 'admin_properties_edit')]
#[IsGranted("ROLE_ADMIN")]
class EditController extends AbstractController
{
    use EditTrait, GetPropertyFromIdTrait;

    public function __invoke(
        string $propertyId, 
        PropertyRepository $propertyRepository, 
        UpdatePropertyService $updateProperty, 
        Request $request
    ): Response
    {   
        $property = $this->getPropertyFromId($propertyId, $propertyRepository);

        $propertyRequest = new UpdatePropertyRequest(
            propertyId: $property->id(),
            name: $property->name(),
            accountId: $property->accountId()
        );

        $form = $this->createForm(AdminUpdatePropertyType::class, $propertyRequest); 

        $needsRedirect = $this->edit($request, $form, function ($form) use ($updateProperty) {
            $propertyRequest = $form->getData();

            $updateProperty($propertyRequest);

            $this->addFlash('success', new TranslatableMessage('Propiedad actualizada con éxito'));
        });

        if ($needsRedirect) {
            return $this->redirectToRoute('admin_properties');
        }

        return $this->render('@waste/admin/properties/edit.html.twig', [
            'property' => $property,
            'form' => $form->createView()
        ]);
    }
}
