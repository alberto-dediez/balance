<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\MaterialityAssessment;

use App\Materiality\Application\MaterialityAssessment\AnswerTopic\AnswerMaterialityAssessmentTopicRequest;
use App\Materiality\Application\MaterialityAssessment\AnswerTopic\AnswerMaterialityAssessmentTopicService;
use App\Materiality\Application\MaterialityAssessment\GetTopic\GetMaterialityAssessmentTopicRequest;
use App\Materiality\Application\MaterialityAssessment\GetTopic\GetMaterialityAssessmentTopicResponse;
use App\Materiality\Application\MaterialityAssessment\GetTopic\GetMaterialityAssessmentTopicService;
use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentNotEditableException;
use App\Materiality\Domain\Model\MaterialityAssessment\InvalidMaterialityAnswerValueException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentAlreadyCompletedException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentNotFoundException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentTopicId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentTopicNotFoundException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/materiality-assessments/{materialityAssessmentId}/topics/{assessmentTopicId}', name: 'materiality_assessments_topic', methods: ['GET', 'POST'])]
class TopicController extends AbstractController
{
    public function __invoke(
        string $materialityAssessmentId,
        string $assessmentTopicId,
        GetMaterialityAssessmentTopicService $getMaterialityAssessmentTopic,
        AnswerMaterialityAssessmentTopicService $answerMaterialityAssessmentTopic,
        MaterialityStepRoute $stepRoute,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        try {
            $topic = $getMaterialityAssessmentTopic(
                new GetMaterialityAssessmentTopicRequest(new MaterialityAssessmentId($materialityAssessmentId), new MaterialityAssessmentTopicId($assessmentTopicId)),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (MaterialityAssessmentNotFoundException|MaterialityAssessmentTopicNotFoundException|\InvalidArgumentException) {
            throw $this->createNotFoundException('Evaluación de materialidad no encontrada');
        }

        $assessment = $topic->assessment;
        $step = $stepRoute->resolve($assessment->subjectType(), $assessment->carbonFootprintId());
        $topicRoute = ['materialityAssessmentId' => $materialityAssessmentId, 'assessmentTopicId' => $assessmentTopicId];

        if ($request->isMethod('POST')) {
            if ($topic->footprint->isCompleted()) {
                $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

                return $this->redirectToRoute('materiality_assessments_topic', $topicRoute);
            }

            if ($assessment->isCompleted()) {
                $this->addFlash('warning', new TranslatableMessage('La evaluación está completada. Reábrela para modificarla.'));

                return $this->redirectToRoute('materiality_assessments_topic', $topicRoute);
            }

            if (!$this->isCsrfTokenValid('materiality_topic_' . $assessmentTopicId, $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            $posted = $request->request->all('answers');
            $values = [];

            foreach ($topic->answers as $answer) {
                $value = $posted[(string) $answer->id()] ?? '';

                if ('' !== $value && (!is_string($value) || !ctype_digit($value))) {
                    $this->addFlash('danger', new TranslatableMessage('No se han podido guardar las respuestas.'));

                    return $this->redirectToRoute('materiality_assessments_topic', $topicRoute);
                }

                $values[(string) $answer->id()] = '' === $value ? null : (int) $value;
            }

            try {
                $response = $answerMaterialityAssessmentTopic(
                    new AnswerMaterialityAssessmentTopicRequest($assessment->id(), $topic->topic->id(), $values),
                    $loggedUser(),
                );
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (InvalidMaterialityAnswerValueException) {
                $this->addFlash('danger', new TranslatableMessage('No se han podido guardar las respuestas.'));

                return $this->redirectToRoute('materiality_assessments_topic', $topicRoute);
            } catch (MaterialityAssessmentNotEditableException) {
                $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

                return $this->redirectToRoute('materiality_assessments_topic', $topicRoute);
            } catch (MaterialityAssessmentAlreadyCompletedException) {
                $this->addFlash('warning', new TranslatableMessage('La evaluación está completada. Reábrela para modificarla.'));

                return $this->redirectToRoute('materiality_assessments_topic', $topicRoute);
            }

            if (null === $response->nextTopicId) {
                return $this->redirectToRoute($step['route'], $step['params']);
            }

            return $this->redirectToRoute('materiality_assessments_topic', [
                'materialityAssessmentId' => $materialityAssessmentId,
                'assessmentTopicId' => (string) $response->nextTopicId,
            ]);
        }

        return $this->render('@materiality/assessment/topic.html.twig', $this->templateVariables($topic) + [
            'assessment' => $assessment,
            'topic' => $topic->topic,
            'answers' => $topic->answers,
            'index' => $topic->index,
            'total' => $topic->total,
            'previousTopicId' => $topic->previousTopicId,
            'nextTopicId' => $topic->nextTopicId,
            'readOnly' => $topic->readOnly,
            'subjectType' => $assessment->subjectType()->value,
            'stepPath' => $this->generateUrl($step['route'], $step['params']),
        ]);
    }

    /** @return array<string, mixed> */
    private function templateVariables(GetMaterialityAssessmentTopicResponse $topic): array
    {
        return match ($topic->assessment->subjectType()) {
            MaterialityAssessmentSubjectType::Property => [
                'propertyCarbonFootprint' => $topic->footprint,
                'propertyCarbonFootprintId' => $topic->assessment->carbonFootprintId(),
            ],
            MaterialityAssessmentSubjectType::Company => [
                'companyCarbonFootprint' => $topic->footprint,
                'companyCarbonFootprintId' => $topic->assessment->carbonFootprintId(),
            ],
        };
    }
}
