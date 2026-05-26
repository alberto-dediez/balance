<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Surplus;

use App\FoodLoss\Application\Surplus\Assess\Composting\AssessCompostingRequest;
use App\FoodLoss\Application\Surplus\Assess\Composting\AssessCompostingService;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\SurplusCompostingAssessmentType;
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

#[Route('/surpluses/edit/{surplusId}/composting', name: 'surpluses_composting')]
class CompostingAssessmentController extends AbstractController
{
    use GetSurplusFromIdTrait;

    public function __invoke(
        string $surplusId,
        SurplusRepository $surplusRepository,
        AssessCompostingService $assessComposting,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $surplus = $this->getSurplusFromId($surplusId, $surplusRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $surplus);

        $composting = $surplus->compostingAssessment();
        $currentCanBeComposted = $composting->isAnswered() ? $composting->canBeComposted() : null;

        $form = $this->createForm(SurplusCompostingAssessmentType::class, [
            'canBeComposted' => $currentCanBeComposted,
            'reasonId' => $composting->reasonId(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $canBeComposted = null;

            try {
                $data = $form->getData();
                $canBeComposted = $data['canBeComposted'];

                $assessComposting(
                    new AssessCompostingRequest(
                        surplusId: $surplus->id(),
                        canBeComposted: $canBeComposted,
                        reasonId: $data['reasonId'],
                    ),
                    $loggedUser(),
                );

                if ($canBeComposted) {
                    $this->addFlash('success', new TranslatableMessage('Se ha registrado un nuevo excedente con destino a compostaje o consumo no alimentario.'));
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

            if (true === $canBeComposted) {
                return $this->redirectToRoute('surpluses');
            }

            return $this->redirectToRoute('surpluses_elimination', ['surplusId' => $surplusId]);
        }

        return $this->render('@food_loss/surpluses/composting.html.twig', [
            'surplus' => $surplus,
            'form' => $form->createView(),
        ]);
    }
}
