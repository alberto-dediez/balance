<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\WasteManager;

use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use App\Waste\Application\WasteManager\Update\UpdateWasteManagerRequest;
use App\Waste\Application\WasteManager\Update\UpdateWasteManagerService;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;
use App\Waste\Infrastructure\Symfony\Form\UpdateWasteManagerType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/waste-managers/edit/{wasteManagerId}', name: 'waste_managers_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetWasteManagerFromIdTrait;

    public function __invoke(
        string $wasteManagerId,
        LoggedUser $loggedUser,
        WasteManagerRepository $wasteManagerRepository,
        UpdateWasteManagerService $updateWasteManager,
        Request $request,
    ): Response {
        $wasteManager = $this->getWasteManagerFromId($wasteManagerId, $wasteManagerRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $wasteManager);

        assert(null !== $wasteManager->name());

        $wasteManagerRequest = new UpdateWasteManagerRequest(
            wasteManagerId: $wasteManager->id(),
            name: $wasteManager->name(),
            isPublic: $wasteManager->isPublic(),
        );

        $form = $this->createForm(UpdateWasteManagerType::class, $wasteManagerRequest);

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateWasteManager, $loggedUser): void {
                $wasteManagerRequest = $form->getData();

                $updateWasteManager($wasteManagerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Gestor de residuos actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('waste_managers');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@waste/waste-managers/edit.html.twig', [
            'wasteManager' => $wasteManager,
            'form' => $form->createView(),
        ]);
    }
}
