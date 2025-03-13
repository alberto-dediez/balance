<?php

namespace App\Waste\Infrastructure\UI\Web\Container;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Component\Translation\TranslatableMessage;
use App\Waste\Domain\Model\Property\PropertyRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Infrastructure\Symfony\Form\UpdateContainerType;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Application\Container\Update\UpdateContainerRequest;
use App\Waste\Application\Container\Update\UpdateContainerService;
use App\Waste\Infrastructure\UI\Web\Container\GetContainerFromIdTrait;
use App\Waste\Infrastructure\UI\Web\Property\OnlyPropertiesFromAccountTrait;

#[Route('/containers/edit/{containerId}', name: 'containers_edit')]
#[IsGranted("ROLE_OWNER")]
class EditController extends AbstractController
{
    use EditTrait, GetContainerFromIdTrait, OnlyPropertiesFromAccountTrait;

    public function __invoke(
        string $containerId, 
        LoggedAccount $loggedAccount,
        ContainerRepository $containerRepository, 
        PropertyRepository $propertyRepository, 
        UpdateContainerService $updateContainer, 
        Request $request
    ): Response
    {   
        $container = $this->getContainerFromId($containerId, $containerRepository);

        $property = $propertyRepository->find($container->propertyId());

        $this->onlyPropertiesFromAccount($property, $loggedAccount());

        $containerRequest = new UpdateContainerRequest(
            containerId: $container->id(),
            wasteId: $container->wasteId(),
            purpose: $container->purpose(),
            measurementMethod: $container->measurementMethod()
        );

        $form = $this->createForm(UpdateContainerType::class, $containerRequest); 

        $needsRedirect = $this->edit($request, $form, function ($form) use ($updateContainer) {
            $containerRequest = $form->getData();

            $updateContainer($containerRequest);

            $this->addFlash('success', new TranslatableMessage('Propiedad actualizada con éxito'));
        });

        if ($needsRedirect) {
            return $this->redirectToRoute('containers');
        }

        return $this->render('@waste/containers/edit.html.twig', [
            'container' => $container,
            'form' => $form->createView()
        ]);
    }
}
