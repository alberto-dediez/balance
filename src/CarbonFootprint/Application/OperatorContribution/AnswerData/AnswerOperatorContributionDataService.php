<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\AnswerData;

use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersProcessor;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionActivitiesRemover;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionConsentRequiredException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

final class AnswerOperatorContributionDataService
{
    public function __construct(
        private OperatorContributionAuthenticator $authenticator,
        private OperatorContributionRepository $contributionRepository,
        private StageAnswersProcessor $answersProcessor,
        private StageAnswersSynchronizer $answersSynchronizer,
        private OperatorContributionActivitiesRemover $activitiesRemover,
    ) {}

    public function __invoke(AnswerOperatorContributionDataRequest $request): void
    {
        $authentication = $this->authenticator->authenticate($request->token);
        $this->authenticator->ensureEditable($this->authenticator->locateCompanyCarbonFootprint($authentication));

        $contribution = $authentication->contribution;

        if (!$contribution->hasConsent()) {
            throw new OperatorContributionConsentRequiredException();
        }

        $answersByScope = [
            Scope::Scope1->value => $this->answersProcessor->validate(Scope::Scope1, $request->scope1Answers, [], null, $contribution->sectionsFor(Scope::Scope1)),
            Scope::Scope2->value => $this->answersProcessor->validate(Scope::Scope2, $request->scope2Answers, [], null, $contribution->sectionsFor(Scope::Scope2)),
        ];

        $before = $contribution->activityReferences();

        foreach ([Scope::Scope1, Scope::Scope2] as $scope) {
            $this->answersSynchronizer->sync(
                $contribution,
                $scope,
                $answersByScope[$scope->value]->sectionAnswers,
                $authentication->user,
                $contribution->sectionsFor($scope),
            );
        }

        $this->activitiesRemover->removeDetached($before, $contribution);

        $contribution->answer();

        $this->contributionRepository->add($contribution);
    }
}
