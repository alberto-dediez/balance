<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Surplus;

use App\FoodLoss\Application\Surplus\Assess\AnimalFeed\AssessAnimalFeedRequest;
use App\FoodLoss\Application\Surplus\Assess\AnimalFeed\AssessAnimalFeedService;
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
        AssessAnimalFeedService $assessAnimalFeed,
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

                $assessAnimalFeed(
                    new AssessAnimalFeedRequest(
                        surplusId: $surplus->id(),
                        canBeAnimalFed: $canBeAnimalFed,
                        reasonId: $data['reasonId'],
                    ),
                    $loggedUser(),
                );

                if ($canBeAnimalFed) {
                    $this->addFlash('success', new TranslatableMessage('Se ha registrado un nuevo excedente con destino a alimentación animal'));
                }
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
