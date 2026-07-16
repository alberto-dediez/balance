<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\AnswerPhase;

use App\CarbonFootprint\Application\Activity\Answer\PhaseAnswersSynchronizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\SectionAnswer;
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
        private PhaseAnswersSynchronizer $answersSynchronizer,
    ) {}

    public function __invoke(AnswerPhaseRequest $request, User $user): void
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null === $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::Edit, $user);

        $sectionsByKey = [];
        foreach ($request->phase->sections() as $section) {
            $sectionsByKey[$section->key] = $section;
        }

        $sectionAnswers = [];
        foreach ($request->sectionAnswers as $sectionAnswer) {
            $definition = $sectionsByKey[$sectionAnswer->definition->key]
                ?? throw new \InvalidArgumentException(sprintf('Section "%s" does not belong to phase "%s".', $sectionAnswer->definition->key, $request->phase->value));

            foreach ($sectionAnswer->answers as $answer) {
                if ($answer->type() !== $definition->activityType) {
                    throw new \InvalidArgumentException(sprintf('Answer of type "%s" does not match section "%s" type.', $answer->type()->value, $definition->key));
                }
            }

            $sectionAnswers[] = new SectionAnswer($definition, $sectionAnswer->answers);
        }

        $phaseSources = [];
        foreach ($request->phase->sections() as $section) {
            $phaseSources[$section->source->value] = $section->source;
        }

        $exclusionsBySource = [];
        foreach ($request->exclusions as $exclusion) {
            if (!isset($phaseSources[$exclusion->source->value])) {
                throw new \InvalidArgumentException(sprintf('Source "%s" does not belong to phase "%s".', $exclusion->source->value, $request->phase->value));
            }

            $exclusionsBySource[$exclusion->source->value] = $exclusion;
        }

        foreach ($sectionAnswers as $sectionAnswer) {
            if ([] !== $sectionAnswer->answers && isset($exclusionsBySource[$sectionAnswer->definition->source->value])) {
                throw new \InvalidArgumentException(sprintf('Section "%s" cannot have answers because its source "%s" is excluded.', $sectionAnswer->definition->key, $sectionAnswer->definition->source->value));
            }
        }

        foreach ($phaseSources as $sourceValue => $source) {
            if (isset($exclusionsBySource[$sourceValue])) {
                $exclusion = $exclusionsBySource[$sourceValue];
                $eventCarbonFootprint->excludeSource($request->phase, $source, $exclusion->reason, $exclusion->comment);
            } else {
                $eventCarbonFootprint->includeSource($request->phase, $source);
            }
        }

        $this->answersSynchronizer->sync($eventCarbonFootprint, $request->phase, $sectionAnswers, $user);

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
