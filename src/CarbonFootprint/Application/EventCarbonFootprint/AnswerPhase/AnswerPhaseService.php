<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\AnswerPhase;

use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersProcessor;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class AnswerPhaseService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $authorizer,
        private StageAnswersProcessor $answersProcessor,
        private StageAnswersSynchronizer $answersSynchronizer,
    ) {}

    public function __invoke(AnswerPhaseRequest $request, User $user): void
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null === $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::Edit, $user);

        $validated = $this->answersProcessor->validate($request->phase, $request->sectionAnswers, $request->exclusions);

        $this->answersProcessor->applyExclusions($eventCarbonFootprint, $request->phase, $validated->exclusionsBySource);

        $this->answersSynchronizer->sync($eventCarbonFootprint, $request->phase, $validated->sectionAnswers, $user);

        $eventCarbonFootprint->answerPhase($request->phase);

        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof AnswerPhaseRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof AnswerPhaseRequest;
    }
}
