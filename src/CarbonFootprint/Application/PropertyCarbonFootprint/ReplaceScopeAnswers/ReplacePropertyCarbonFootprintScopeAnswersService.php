<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\ReplaceScopeAnswers;

use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersProcessor;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ReplacePropertyCarbonFootprintScopeAnswersService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private PropertyCarbonFootprintAuthorizer $authorizer,
        private StageAnswersProcessor $answersProcessor,
        private StageAnswersSynchronizer $answersSynchronizer,
    ) {}

    public function __invoke(ReplacePropertyCarbonFootprintScopeAnswersRequest $request, User $user): ReplacePropertyCarbonFootprintScopeAnswersResponse
    {
        $propertyCarbonFootprint = $this->propertyCarbonFootprintRepository->find($request->propertyCarbonFootprintId);

        if (null === $propertyCarbonFootprint) {
            throw new \InvalidArgumentException('PropertyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($propertyCarbonFootprint, SecurityAction::Edit, $user);

        $validated = $this->answersProcessor->validate($request->scope, $request->sectionAnswers, []);

        $exclusions = $propertyCarbonFootprint->exclusionsFor($request->scope);

        /** @var array<string, Source> $includedSources */
        $includedSources = [];
        $activities = 0;

        foreach ($validated->sectionAnswers as $sectionAnswer) {
            $activities += count($sectionAnswer->answers);

            $source = $sectionAnswer->definition->source;

            if ([] === $sectionAnswer->answers || !isset($exclusions[$source->value])) {
                continue;
            }

            $propertyCarbonFootprint->includeSource($request->scope, $source);
            $includedSources[$source->value] = $source;
        }

        $this->answersSynchronizer->sync($propertyCarbonFootprint, $request->scope, $validated->sectionAnswers, $user);

        $this->propertyCarbonFootprintRepository->add($propertyCarbonFootprint);

        return new ReplacePropertyCarbonFootprintScopeAnswersResponse($activities, array_values($includedSources));
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof ReplacePropertyCarbonFootprintScopeAnswersRequest);

        return $this->__invoke($command, $user);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ReplacePropertyCarbonFootprintScopeAnswersRequest;
    }
}
