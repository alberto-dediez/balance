<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\SurplusCause;

use App\FoodLoss\Application\SurplusCause\Update\UpdateSurplusCauseRequest;
use App\FoodLoss\Application\SurplusCause\Update\UpdateSurplusCauseService;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdateSurplusCauseType;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/surplus-causes/edit/{surplusCauseId}', name: 'admin_surplus_causes_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetSurplusCauseFromIdTrait;

    public function __invoke(
        string $surplusCauseId,
        SurplusCauseRepository $surplusCauseRepository,
        UpdateSurplusCauseService $updateSurplusCause,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $surplusCause = $this->getSurplusCauseFromId($surplusCauseId, $surplusCauseRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $surplusCause);

        $surplusCauseRequest = new UpdateSurplusCauseRequest(
            surplusCauseId: $surplusCause->id(),
            name: $surplusCause->name(),
        );

        try {
            $form = $this->createForm(UpdateSurplusCauseType::class, $surplusCauseRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateSurplusCause, $loggedUser): void {
                $surplusCauseRequest = $form->getData();

                $updateSurplusCause($surplusCauseRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Causa de excedente actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_surplus_causes');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/admin/surplus-causes/edit.html.twig', [
            'surplusCause' => $surplusCause,
            'form' => $form->createView(),
        ]);
    }
}
