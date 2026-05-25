<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Surplus;

use App\FoodLoss\Application\Surplus\Assess\AssessSurplusRequest;
use App\FoodLoss\Application\Surplus\Assess\AssessSurplusService;
use App\FoodLoss\Application\Surplus\Update\UpdateSurplusRequest;
use App\FoodLoss\Application\Surplus\Update\UpdateSurplusService;
use App\FoodLoss\Domain\Model\Surplus\SurplusDestination;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\FoodLoss\Domain\Model\Surplus\TransformationAssessment;
use App\FoodLoss\Infrastructure\Symfony\Form\SurplusTransformationAssessmentType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/surpluses/edit/{surplusId}/transformation', name: 'surpluses_transformation')]
class TransformationAssessmentController extends AbstractController
{
    use GetSurplusFromIdTrait;

    public function __invoke(
        string $surplusId,
        SurplusRepository $surplusRepository,
        UpdateSurplusService $updateSurplus,
        AssessSurplusService $assessSurplus,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $surplus = $this->getSurplusFromId($surplusId, $surplusRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $surplus);

        $transformation = $surplus->transformationAssessment();
        $currentCanBeTransformed = $transformation->isAnswered() ? $transformation->canBeTransformed() : null;

        $form = $this->createForm(SurplusTransformationAssessmentType::class, [
            'canBeTransformed' => $currentCanBeTransformed,
            'reasonId' => $transformation->reasonId(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $canBeTransformed = null;

            try {
                $data = $form->getData();
                $canBeTransformed = $data['canBeTransformed'];

                if (true === $canBeTransformed) {
                    $assessSurplus(
                        new AssessSurplusRequest(
                            surplusId: $surplus->id(),
                            destination: SurplusDestination::Transformation,
                        ),
                        $loggedUser(),
                    );
                } else {
                    $updateSurplus(
                        new UpdateSurplusRequest(
                            surplusId: $surplus->id(),
                            date: $surplus->date(),
                            serviceCategoryId: $surplus->serviceCategoryId(),
                            criticalPointId: $surplus->criticalPointId(),
                            surplusCauseId: $surplus->surplusCauseId(),
                            foodType: $surplus->foodType(),
                            quantity: $surplus->quantity(),
                            unit: $surplus->unit(),
                            preventionAssessment: $surplus->preventionAssessment(),
                            transformationAssessment: new TransformationAssessment(
                                canBeTransformed: $canBeTransformed,
                                reasonId: $data['reasonId'],
                            ),
                            humanDonationAssessment: $surplus->humanDonationAssessment(),
                            animalFeedAssessment: $surplus->animalFeedAssessment(),
                            compostingAssessment: $surplus->compostingAssessment(),
                            eliminationAssessment: $surplus->eliminationAssessment(),
                        ),
                        $loggedUser(),
                    );
                }

                $this->addFlash('success', new TranslatableMessage('Transformación actualizada con éxito'));
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (\Exception) {
                $this->addFlash('danger', new TranslatableMessage('Error inesperado. No se ha podido actualizar correctamente.'));
            }

            assert($form instanceof Form);
            $clickedButton = $form->getClickedButton();

            if ($clickedButton instanceof FormInterface && 'saveAndReturn' === $clickedButton->getName()) {
                return $this->redirectToRoute('surpluses');
            }

            if (true === $canBeTransformed) {
                return $this->redirectToRoute('surpluses');
            }

            return $this->redirectToRoute('surpluses_human_donation', ['surplusId' => $surplusId]);
        }

        return $this->render('@food_loss/surpluses/transformation.html.twig', [
            'surplus' => $surplus,
            'form' => $form->createView(),
        ]);
    }
}
