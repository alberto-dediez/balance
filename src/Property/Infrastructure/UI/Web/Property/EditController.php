<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Property;

use App\Property\Application\Property\Update\UpdatePropertyRequest;
use App\Property\Application\Property\Update\UpdatePropertyService;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Property\Infrastructure\Symfony\Form\UpdatePropertyType;
use App\Property\Infrastructure\UI\Web\Admin\Property\GetPropertyFromIdTrait;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/properties/edit/{propertyId}', name: 'properties_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetPropertyFromIdTrait;

    public function __invoke(
        string $propertyId,
        LoggedUser $loggedUser,
        PropertyRepository $propertyRepository,
        UpdatePropertyService $updateProperty,
        Request $request,
    ): Response {
        $property = $this->getPropertyFromId($propertyId, $propertyRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $property);

        $propertyRequest = new UpdatePropertyRequest(
            propertyId: $property->id(),
            name: $property->name(),
            accountId: $property->accountId(),
            info: $property->info(),
        );

        $form = $this->createForm(UpdatePropertyType::class, $propertyRequest);

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateProperty, $loggedUser): void {
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

        return $this->render('@property/properties/edit.html.twig', [
            'property' => $property,
            'form' => $form->createView(),
        ]);
    }
}
