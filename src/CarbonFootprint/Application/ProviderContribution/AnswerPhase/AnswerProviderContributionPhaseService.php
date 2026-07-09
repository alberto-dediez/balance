<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\AnswerPhase;

use App\CarbonFootprint\Application\Activity\Answer\PhaseAnswersSynchronizer;
use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionRepository;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionTokenInvalidException;
use App\EventOperations\Domain\Model\Provider\ProviderId;

final class AnswerProviderContributionPhaseService
{
    public function __construct(
        private ProviderContributionAuthenticator $authenticator,
        private ProviderContributionRepository $providerContributionRepository,
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private PhaseAnswersSynchronizer $answersSynchronizer,
    ) {}

    public function __invoke(AnswerProviderContributionPhaseRequest $request): void
    {
        $authentication = $this->authenticator->authenticate($request->token);
        $providerContribution = $authentication->providerContribution;

        $eventCarbonFootprintId = $providerContribution->eventCarbonFootprintId();
        $providerId = $providerContribution->providerId();

        if (null === $eventCarbonFootprintId || !$providerId instanceof ProviderId) {
            throw new ProviderContributionTokenInvalidException();
        }

        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($eventCarbonFootprintId);

        if (null === $eventCarbonFootprint) {
            throw new ProviderContributionTokenInvalidException();
        }

        if ($eventCarbonFootprint->status()->isCompleted()) {
            throw new ProviderContributionClosedException();
        }

        $sectionsByKey = [];
        foreach ($request->phase->providerSections() as $section) {
            $sectionsByKey[$section->key] = $section;
        }

        $sectionAnswers = [];
        foreach ($request->sectionAnswers as $sectionAnswer) {
            $definition = $sectionsByKey[$sectionAnswer->definition->key]
                ?? throw new \InvalidArgumentException(sprintf('Section "%s" is not available to providers on phase "%s".', $sectionAnswer->definition->key, $request->phase->value));

            foreach ($sectionAnswer->answers as $answer) {
                if ($answer->type() !== $definition->activityType) {
                    throw new \InvalidArgumentException(sprintf('Answer of type "%s" does not match section "%s" type.', $answer->type()->value, $definition->key));
                }
            }

            $sectionAnswers[] = new SectionAnswer($definition, $sectionAnswer->answers);
        }

        $this->answersSynchronizer->sync(
            $eventCarbonFootprint,
            $request->phase,
            $sectionAnswers,
            $authentication->user,
            $request->phase->providerSections(),
            $providerId,
        );

        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $providerContribution->answerPhase($request->phase);

        $this->providerContributionRepository->add($providerContribution);
    }
}
