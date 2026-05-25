<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Surplus;

use App\FoodLoss\Application\Surplus\Assess\HumanDonation\AssessHumanDonationRequest;
use App\FoodLoss\Application\Surplus\Assess\HumanDonation\AssessHumanDonationService;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\SurplusHumanDonationAssessmentType;
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

#[Route('/surpluses/edit/{surplusId}/human-donation', name: 'surpluses_human_donation')]
class HumanDonationAssessmentController extends AbstractController
{
    use GetSurplusFromIdTrait;

    public function __invoke(
        string $surplusId,
        SurplusRepository $surplusRepository,
        AssessHumanDonationService $assessHumanDonation,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $surplus = $this->getSurplusFromId($surplusId, $surplusRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $surplus);

        $humanDonation = $surplus->humanDonationAssessment();
        $currentCanBeHumanDonated = $humanDonation->isAnswered() ? $humanDonation->canBeHumanDonated() : null;

        $form = $this->createForm(SurplusHumanDonationAssessmentType::class, [
            'canBeHumanDonated' => $currentCanBeHumanDonated,
            'reasonId' => $humanDonation->reasonId(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $canBeHumanDonated = null;

            try {
                $data = $form->getData();
                $canBeHumanDonated = $data['canBeHumanDonated'];

                $assessHumanDonation(
                    new AssessHumanDonationRequest(
                        surplusId: $surplus->id(),
                        canBeHumanDonated: $canBeHumanDonated,
                        reasonId: $data['reasonId'],
                    ),
                    $loggedUser(),
                );

                $this->addFlash('success', new TranslatableMessage('Donación humana actualizada con éxito'));
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

            if (true === $canBeHumanDonated) {
                return $this->redirectToRoute('surpluses');
            }

            return $this->redirectToRoute('surpluses_animal_feed', ['surplusId' => $surplusId]);
        }

        return $this->render('@food_loss/surpluses/human-donation.html.twig', [
            'surplus' => $surplus,
            'form' => $form->createView(),
        ]);
    }
}
