<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityTopic\List;

use App\Materiality\Application\MaterialityTopic\MaterialityTopicAuthorizer;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListMaterialityTopicsService
{
    public function __construct(
        private MaterialityTopicRepository $materialityTopicRepository,
        private MaterialityTopicAuthorizer $authorizer,
    ) {}

    public function __invoke(ListMaterialityTopicsRequest $request, User $user): ListMaterialityTopicsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new MaterialityTopic(id: MaterialityTopicId::generate()),
            SecurityAction::List,
            $user,
        );

        return new ListMaterialityTopicsResponse(
            materialityTopics: $this->materialityTopicRepository->findByFilter($request),
            totalResults: $this->materialityTopicRepository->totalResultsByFilter($request),
        );
    }
}
