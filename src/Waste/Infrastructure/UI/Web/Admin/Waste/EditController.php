<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Admin\Waste;

use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use App\Waste\Application\Waste\Update\UpdateWasteRequest;
use App\Waste\Application\Waste\Update\UpdateWasteService;
use App\Waste\Domain\Model\Waste\WasteRepository;
use App\Waste\Infrastructure\Symfony\Form\UpdateWasteType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/wastes/edit/{wasteId}', name: 'admin_wastes_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetWasteFromIdTrait;

    public function __invoke(
        string $wasteId,
        WasteRepository $wasteRepository,
        UpdateWasteService $updateWaste,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $waste = $this->getWasteFromId($wasteId, $wasteRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $waste);

        $wasteRequest = new UpdateWasteRequest(
            wasteId: $waste->id(),
            name: $waste->name(),
            isDangerous: $waste->isDangerous(),
            density: $waste->density(),
            color: $waste->color(),
        );

        try {
            $form = $this->createForm(UpdateWasteType::class, $wasteRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateWaste, $loggedUser): void {
                $wasteRequest = $form->getData();

                $updateWaste($wasteRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Tipología actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_wastes');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@waste/admin/wastes/edit.html.twig', [
            'waste' => $waste,
            'form' => $form->createView(),
        ]);
    }
}
