<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\MaterialityAssessment;

use App\Materiality\Application\MaterialityAssessment\AnswerTopic\AnswerMaterialityAssessmentTopicRequest;
use App\Materiality\Application\MaterialityAssessment\AnswerTopic\AnswerMaterialityAssessmentTopicResponse;
use App\Materiality\Application\MaterialityAssessment\AnswerTopic\AnswerMaterialityAssessmentTopicService;
use App\Materiality\Application\MaterialityAssessment\GetTopic\GetMaterialityAssessmentTopicRequest;
use App\Materiality\Application\MaterialityAssessment\GetTopic\GetMaterialityAssessmentTopicResponse;
use App\Materiality\Application\MaterialityAssessment\GetTopic\GetMaterialityAssessmentTopicService;
use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentNotEditableException;
use App\Materiality\Domain\Model\MaterialityAssessment\InvalidMaterialityAnswerValueException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentAlreadyCompletedException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentNotFoundException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentTopicId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentTopicNotFoundException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        TranslatorInterface $translator,
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

        $step = $stepRoute->resolve($topic->assessment->subjectType(), $topic->assessment->carbonFootprintId());
        $stepParams = $step['params'] + ['_fragment' => 'topic-' . $assessmentTopicId];

        if (!$request->isMethod('POST')) {
            return $this->redirectToRoute($step['route'], $stepParams);
        }

        try {
            $response = $this->answer($topic, $assessmentTopicId, $request, $answerMaterialityAssessmentTopic, $loggedUser());
        } catch (InvalidMaterialityAnswerValueException|MaterialityAssessmentNotEditableException|MaterialityAssessmentAlreadyCompletedException $e) {
            [$status, $type, $message] = match (true) {
                $e instanceof InvalidMaterialityAnswerValueException => [Response::HTTP_UNPROCESSABLE_ENTITY, 'danger', new TranslatableMessage('No se han podido guardar las respuestas.')],
                $e instanceof MaterialityAssessmentNotEditableException => [Response::HTTP_CONFLICT, 'warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.')],
                default => [Response::HTTP_CONFLICT, 'warning', new TranslatableMessage('La evaluación está completada. Reábrela para modificarla.')],
            };

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['message' => $message->trans($translator)], $status);
            }

            $this->addFlash($type, $message);

            return $this->redirectToRoute($step['route'], $stepParams);
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'topicAnswered' => $response->topicAnswered,
                'topicTotal' => $response->topicTotal,
                'answered' => $response->answered,
                'total' => $response->total,
                'fullyAnswered' => $response->fullyAnswered,
            ]);
        }

        return $this->redirectToRoute($step['route'], $stepParams);
    }

    private function answer(
        GetMaterialityAssessmentTopicResponse $topic,
        string $assessmentTopicId,
        Request $request,
        AnswerMaterialityAssessmentTopicService $answerMaterialityAssessmentTopic,
        User $user,
    ): AnswerMaterialityAssessmentTopicResponse {
        if ($topic->footprint->isCompleted()) {
            throw new MaterialityAssessmentNotEditableException();
        }

        if ($topic->assessment->isCompleted()) {
            throw new MaterialityAssessmentAlreadyCompletedException($topic->assessment->id());
        }

        if (!$this->isCsrfTokenValid('materiality_topic_' . $assessmentTopicId, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        $posted = $request->request->all('answers');
        $values = [];

        foreach ($topic->answers as $answer) {
            $value = $posted[(string) $answer->id()] ?? '';

            if ('' !== $value && (!is_string($value) || !ctype_digit($value))) {
                throw InvalidMaterialityAnswerValueException::outOfScale((int) $value, (string) $answer->id());
            }

            $values[(string) $answer->id()] = '' === $value ? null : (int) $value;
        }

        try {
            return $answerMaterialityAssessmentTopic(
                new AnswerMaterialityAssessmentTopicRequest($topic->assessment->id(), $topic->topic->id(), $values),
                $user,
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }
    }
}
