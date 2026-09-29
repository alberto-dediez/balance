<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Event;

use App\Materiality\Application\MaterialityQuestion\Remove\RemoveMaterialityQuestionRequest;
use App\Materiality\Application\MaterialityQuestion\Remove\RemoveMaterialityQuestionService;
use App\Materiality\Application\MaterialityQuestion\Update\UpdateMaterialityQuestionRequest;
use App\Materiality\Application\MaterialityQuestion\Update\UpdateMaterialityQuestionService;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRemoved;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class UnlinkOrRemoveQuestionsOnMaterialitySectorRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private MaterialityQuestionRepository $materialityQuestionRepository,
        private UpdateMaterialityQuestionService $updateMaterialityQuestion,
        private RemoveMaterialityQuestionService $removeMaterialityQuestion,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof MaterialitySectorRemoved);

        foreach ($this->materialityQuestionRepository->findBySectorId($event->materialitySectorId()) as $materialityQuestion) {
            if (1 === count($materialityQuestion->sectors())) {
                ($this->removeMaterialityQuestion)(
                    new RemoveMaterialityQuestionRequest($materialityQuestion->id()),
                    $this->loggedUser->__invoke(),
                );

                continue;
            }

            ($this->updateMaterialityQuestion)(
                $this->unlinkRequest($materialityQuestion, $event->materialitySectorId()),
                $this->loggedUser->__invoke(),
            );
        }
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof MaterialitySectorRemoved;
    }

    private function unlinkRequest(MaterialityQuestion $materialityQuestion, MaterialitySectorId $removedSectorId): UpdateMaterialityQuestionRequest
    {
        return new UpdateMaterialityQuestionRequest(
            materialityQuestionId: $materialityQuestion->id(),
            criterion: $materialityQuestion->criterion(),
            topicId: $materialityQuestion->topicId(),
            text: $materialityQuestion->text(),
            help: $materialityQuestion->help(),
            answerScale: $materialityQuestion->answerScale(),
            weight: $materialityQuestion->weight(),
            position: $materialityQuestion->position(),
            active: $materialityQuestion->isActive(),
            sectorIds: array_values(array_filter(
                $materialityQuestion->sectorIds(),
                static fn(MaterialitySectorId $sectorId) => !$sectorId->equals($removedSectorId),
            )),
        );
    }
}
