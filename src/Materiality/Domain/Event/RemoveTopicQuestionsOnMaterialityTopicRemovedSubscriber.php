<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Event;

use App\Materiality\Application\MaterialityQuestion\Remove\RemoveMaterialityQuestionRequest;
use App\Materiality\Application\MaterialityQuestion\Remove\RemoveMaterialityQuestionService;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRemoved;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class RemoveTopicQuestionsOnMaterialityTopicRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private MaterialityQuestionRepository $materialityQuestionRepository,
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

        assert($event instanceof MaterialityTopicRemoved);

        foreach ($this->materialityQuestionRepository->findByTopicId($event->materialityTopicId()) as $materialityQuestion) {
            $request = new RemoveMaterialityQuestionRequest($materialityQuestion->id());

            ($this->removeMaterialityQuestion)($request, $this->loggedUser->__invoke());
        }
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof MaterialityTopicRemoved;
    }
}
