<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityQuestion\List;

use App\Materiality\Application\MaterialityQuestion\MaterialityQuestionAuthorizer;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListMaterialityQuestionsService
{
    public function __construct(
        private MaterialityQuestionRepository $materialityQuestionRepository,
        private MaterialityTopicRepository $materialityTopicRepository,
        private MaterialityQuestionAuthorizer $authorizer,
    ) {}

    public function __invoke(ListMaterialityQuestionsRequest $request, User $user): ListMaterialityQuestionsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new MaterialityQuestion(id: MaterialityQuestionId::generate()),
            SecurityAction::List,
            $user,
        );

        $materialityQuestions = $this->materialityQuestionRepository->findByFilter($request);

        $topicsById = [];

        foreach ($materialityQuestions as $materialityQuestion) {
            $topicId = $materialityQuestion->topicId();

            if (null === $topicId || isset($topicsById[(string) $topicId])) {
                continue;
            }

            $topic = $this->materialityTopicRepository->find($topicId);

            if (null !== $topic) {
                $topicsById[(string) $topicId] = $topic;
            }
        }

        return new ListMaterialityQuestionsResponse(
            materialityQuestions: $materialityQuestions,
            totalResults: $this->materialityQuestionRepository->totalResultsByFilter($request),
            topicsById: $topicsById,
        );
    }
}
