<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\MaterialityAssessment;

use App\Materiality\Application\MaterialityAssessment\GetStep\GetMaterialityStepRequest;
use App\Materiality\Application\MaterialityAssessment\GetStep\GetMaterialityStepService;
use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentNotEditableException;
use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentScope2NotAnsweredException;
use App\Materiality\Application\MaterialityAssessment\Start\StartMaterialityAssessmentRequest;
use App\Materiality\Application\MaterialityAssessment\Start\StartMaterialityAssessmentService;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentAlreadyExistsException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorNotFoundException;
use App\Materiality\Infrastructure\Symfony\Form\StartMaterialityAssessmentType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/materiality-assessments/{subjectType}/{carbonFootprintId}/start', name: 'materiality_assessments_start', requirements: ['subjectType' => 'property|company'], methods: ['POST'])]
class StartController extends AbstractController
{
    public function __invoke(
        string $subjectType,
        string $carbonFootprintId,
        GetMaterialityStepService $getMaterialityStep,
        StartMaterialityAssessmentService $startMaterialityAssessment,
        MaterialityStepRoute $stepRoute,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $type = MaterialityAssessmentSubjectType::from($subjectType);
        $step = $stepRoute->resolve($type, $carbonFootprintId);

        try {
            $current = $getMaterialityStep(new GetMaterialityStepRequest($type, $carbonFootprintId), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException('El cálculo de huella de carbono no existe');
        }

        $form = $this->createForm(StartMaterialityAssessmentType::class, null, [
            'previous_year' => $current->previousYear,
            'default_sector_id' => $current->previousSectorId,
            'sectors_available' => [] !== $current->sectors,
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido iniciar la evaluación de materialidad.'));

            return $this->redirectToRoute($step['route'], $step['params']);
        }

        $sectorId = $form->has('sectorId') ? $form->get('sectorId')->getData() : null;
        $copyPreviousAnswers = $form->has('copyPreviousAnswers') && true === $form->get('copyPreviousAnswers')->getData();

        try {
            $response = $startMaterialityAssessment(
                new StartMaterialityAssessmentRequest(
                    subjectType: $type,
                    carbonFootprintId: $carbonFootprintId,
                    sectorId: $sectorId instanceof MaterialitySectorId ? $sectorId : null,
                    copyPreviousAnswers: $copyPreviousAnswers,
                ),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (MaterialityAssessmentNotEditableException) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

            return $this->redirectToRoute($step['route'], $step['params']);
        } catch (MaterialityAssessmentScope2NotAnsweredException) {
            $this->addFlash('warning', new TranslatableMessage('Responde el alcance 2 antes de evaluar la materialidad.'));

            return $this->redirectToRoute($step['route'], $step['params']);
        } catch (MaterialityAssessmentAlreadyExistsException) {
            return $this->redirectToRoute($step['route'], $step['params']);
        } catch (MaterialitySectorNotFoundException) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido iniciar la evaluación de materialidad.'));

            return $this->redirectToRoute($step['route'], $step['params']);
        }

        if (null === $response->firstTopicId) {
            return $this->redirectToRoute($step['route'], $step['params']);
        }

        return $this->redirectToRoute('materiality_assessments_topic', [
            'materialityAssessmentId' => (string) $response->materialityAssessmentId,
            'assessmentTopicId' => (string) $response->firstTopicId,
        ]);
    }
}
