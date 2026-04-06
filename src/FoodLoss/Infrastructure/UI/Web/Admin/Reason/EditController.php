<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\Reason;

use App\FoodLoss\Application\Reason\Update\UpdateReasonRequest;
use App\FoodLoss\Application\Reason\Update\UpdateReasonService;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdateReasonType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/reasons/edit/{reasonId}', name: 'admin_reasons_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetReasonFromIdTrait;

    public function __invoke(
        string $reasonId,
        ReasonRepository $reasonRepository,
        UpdateReasonService $updateReason,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $reason = $this->getReasonFromId($reasonId, $reasonRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $reason);

        $reasonRequest = new UpdateReasonRequest(
            reasonId: $reason->id(),
            description: $reason->description(),
            priority: $reason->priority(),
        );

        try {
            $form = $this->createForm(UpdateReasonType::class, $reasonRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateReason, $loggedUser): void {
                $reasonRequest = $form->getData();

                $updateReason($reasonRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Motivo actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_reasons');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/admin/reasons/edit.html.twig', [
            'reason' => $reason,
            'form' => $form->createView(),
        ]);
    }
}
