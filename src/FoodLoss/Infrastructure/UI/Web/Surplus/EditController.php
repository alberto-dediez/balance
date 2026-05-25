<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Surplus;

use App\FoodLoss\Application\Surplus\Update\UpdateSurplusRequest;
use App\FoodLoss\Application\Surplus\Update\UpdateSurplusService;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdateSurplusType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/surpluses/edit/{surplusId}', name: 'surpluses_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetSurplusFromIdTrait;

    public function __invoke(
        string $surplusId,
        SurplusRepository $surplusRepository,
        UpdateSurplusService $updateSurplus,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $surplus = $this->getSurplusFromId($surplusId, $surplusRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $surplus);

        $surplusRequest = new UpdateSurplusRequest(
            surplusId: $surplus->id(),
            date: $surplus->date(),
            serviceCategoryId: $surplus->serviceCategoryId(),
            criticalPointId: $surplus->criticalPointId(),
            surplusCauseId: $surplus->surplusCauseId(),
            foodType: $surplus->foodType(),
            quantity: $surplus->quantity(),
            unit: $surplus->unit(),
            preventionAssessment: $surplus->preventionAssessment(),
            transformationAssessment: $surplus->transformationAssessment(),
            humanDonationAssessment: $surplus->humanDonationAssessment(),
            animalFeedAssessment: $surplus->animalFeedAssessment(),
            compostingAssessment: $surplus->compostingAssessment(),
            eliminationAssessment: $surplus->eliminationAssessment(),
        );

        try {
            $form = $this->createForm(UpdateSurplusType::class, $surplusRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateSurplus, $loggedUser): void {
                $updateSurplus($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Excedente actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('surpluses');
            }

            if ($form->isSubmitted() && $form->isValid()) {
                return $this->redirectToRoute('surpluses_prevention', ['surplusId' => $surplusId]);
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/surpluses/edit.html.twig', [
            'surplus' => $surplus,
            'form' => $form->createView(),
        ]);
    }
}
