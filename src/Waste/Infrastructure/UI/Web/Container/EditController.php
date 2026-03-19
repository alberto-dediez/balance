<?php

namespace App\Waste\Infrastructure\UI\Web\Container;

use App\Security\Application\Action;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\UnauthorizedActionException;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Infrastructure\Symfony\Form\UpdateContainerType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Application\Container\Update\UpdateContainerRequest;
use App\Waste\Application\Container\Update\UpdateContainerService;
use App\Waste\Infrastructure\UI\Web\Container\GetContainerFromIdTrait;

#[Route('/containers/edit/{containerId}', name: 'containers_edit')]
class EditController extends AbstractController
{
    use EditTrait, GetContainerFromIdTrait;

    public function __invoke(
        string $containerId, 
        ContainerRepository $containerRepository, 
        UpdateContainerService $updateContainer, 
        LoggedUser $loggedUser,
        Request $request
    ): Response
    {   
        $container = $this->getContainerFromId($containerId, $containerRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $container);

        $containerRequest = new UpdateContainerRequest(
            containerId: $container->id(),
            wasteId: $container->wasteId(),
            purpose: $container->purpose(),
            measurementMethod: $container->measurementMethod(),
            volume: $container->volume(),
            wasteManagerId: $container->wasteManagerId()
        );

        try {
            $form = $this->createForm(UpdateContainerType::class, $containerRequest); 

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateContainer, $loggedUser): void {
                $containerRequest = $form->getData();

                $updateContainer($containerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Propiedad actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('containers');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@waste/containers/edit.html.twig', [
            'container' => $container,
            'form' => $form->createView()
        ]);
    }
}
