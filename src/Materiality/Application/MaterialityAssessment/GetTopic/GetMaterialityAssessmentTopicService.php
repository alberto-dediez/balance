<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\GetTopic;

use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentAuthorizer;
use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentFootprints;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentNotFoundException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class GetMaterialityAssessmentTopicService
{
    public function __construct(
        private MaterialityAssessmentRepository $materialityAssessmentRepository,
        private MaterialityAssessmentFootprints $footprints,
        private MaterialityAssessmentAuthorizer $authorizer,
    ) {}

    public function __invoke(GetMaterialityAssessmentTopicRequest $request, User $user): GetMaterialityAssessmentTopicResponse
    {
        $assessment = $this->materialityAssessmentRepository->find($request->materialityAssessmentId);

        if (null === $assessment) {
            throw new MaterialityAssessmentNotFoundException($request->materialityAssessmentId);
        }

        $this->authorizer->denyUnlessCanDoThat($assessment, SecurityAction::View, $user);

        $topic = $assessment->topic($request->assessmentTopicId);
        $topics = $assessment->topics();
        $index = (int) array_search($topic, $topics, true);

        $footprint = $this->footprints->find($assessment->subjectType(), $assessment->carbonFootprintId());

        return new GetMaterialityAssessmentTopicResponse(
            assessment: $assessment,
            topic: $topic,
            answers: $assessment->answersOf($topic->topicId()),
            index: $index + 1,
            total: count($topics),
            previousTopicId: $assessment->previousTopicBefore($topic->id())?->id(),
            nextTopicId: $assessment->nextTopicAfter($topic->id())?->id(),
            footprint: $footprint,
            readOnly: $footprint->isCompleted() || $assessment->isCompleted(),
        );
    }
}
