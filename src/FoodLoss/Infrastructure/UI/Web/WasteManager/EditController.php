<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\WasteManager;

use App\FoodLoss\Application\WasteManager\Update\UpdateWasteManagerRequest;
use App\FoodLoss\Application\WasteManager\Update\UpdateWasteManagerService;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdateWasteManagerType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/food-waste-managers/edit/{wasteManagerId}', name: 'food_waste_managers_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetWasteManagerFromIdTrait;

    public function __invoke(
        string $wasteManagerId,
        WasteManagerRepository $wasteManagerRepository,
        UpdateWasteManagerService $updateWasteManager,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $wasteManager = $this->getWasteManagerFromId($wasteManagerId, $wasteManagerRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $wasteManager);

        $wasteManagerRequest = new UpdateWasteManagerRequest(
            wasteManagerId: $wasteManager->id(),
            name: $wasteManager->name(),
            animalFeedAuthorized: $wasteManager->animalFeedAuthorized(),
            compostAuthorized: $wasteManager->compostAuthorized(),
        );

        try {
            $form = $this->createForm(UpdateWasteManagerType::class, $wasteManagerRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateWasteManager, $loggedUser): void {
                $updateRequest = $form->getData();

                $updateWasteManager($updateRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Gestor de residuos actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('food_waste_managers');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/waste-managers/edit.html.twig', [
            'wasteManager' => $wasteManager,
            'form' => $form->createView(),
        ]);
    }
}
