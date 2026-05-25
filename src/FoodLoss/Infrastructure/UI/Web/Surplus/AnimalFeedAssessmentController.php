<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Surplus;

use App\FoodLoss\Application\Surplus\Assess\AssessSurplusRequest;
use App\FoodLoss\Application\Surplus\Assess\AssessSurplusService;
use App\FoodLoss\Application\Surplus\Update\UpdateSurplusRequest;
use App\FoodLoss\Application\Surplus\Update\UpdateSurplusService;
use App\FoodLoss\Domain\Model\Surplus\AnimalFeedAssessment;
use App\FoodLoss\Domain\Model\Surplus\SurplusDestination;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\SurplusAnimalFeedAssessmentType;
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

#[Route('/surpluses/edit/{surplusId}/animal-feed', name: 'surpluses_animal_feed')]
class AnimalFeedAssessmentController extends AbstractController
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

        $animalFeed = $surplus->animalFeedAssessment();
        $currentCanBeAnimalFed = $animalFeed->isAnswered() ? $animalFeed->canBeAnimalFed() : null;

        $form = $this->createForm(SurplusAnimalFeedAssessmentType::class, [
            'canBeAnimalFed' => $currentCanBeAnimalFed,
            'reasonId' => $animalFeed->reasonId(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $canBeAnimalFed = null;

            try {
                $data = $form->getData();
                $canBeAnimalFed = $data['canBeAnimalFed'];

                if (true === $canBeAnimalFed) {
                    $assessSurplus(
                        new AssessSurplusRequest(
                            surplusId: $surplus->id(),
                            destination: SurplusDestination::AnimalFeed,
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
                            transformationAssessment: $surplus->transformationAssessment(),
                            humanDonationAssessment: $surplus->humanDonationAssessment(),
                            animalFeedAssessment: new AnimalFeedAssessment(
                                canBeAnimalFed: $canBeAnimalFed,
                                reasonId: $data['reasonId'],
                            ),
                            compostingAssessment: $surplus->compostingAssessment(),
                            eliminationAssessment: $surplus->eliminationAssessment(),
                        ),
                        $loggedUser(),
                    );
                }

                $this->addFlash('success', new TranslatableMessage('Alimentación animal actualizada con éxito'));
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

            if (true === $canBeAnimalFed) {
                return $this->redirectToRoute('surpluses');
            }

            return $this->redirectToRoute('surpluses_composting', ['surplusId' => $surplusId]);
        }

        return $this->render('@food_loss/surpluses/animal-feed.html.twig', [
            'surplus' => $surplus,
            'form' => $form->createView(),
        ]);
    }
}
