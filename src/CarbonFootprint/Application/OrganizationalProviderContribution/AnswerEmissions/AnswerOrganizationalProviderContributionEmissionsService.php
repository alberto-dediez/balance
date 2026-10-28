<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\AnswerEmissions;

use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionConsentRequiredException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionTokenInvalidException;
use App\EventOperations\Domain\Model\Provider\ProviderId;

final class AnswerOrganizationalProviderContributionEmissionsService
{
    public function __construct(
        private OrganizationalProviderContributionAuthenticator $authenticator,
        private OrganizationalProviderContributionRepository $contributionRepository,
        private OrganizationalCarbonFootprintLocator $locator,
        private StageAnswersSynchronizer $answersSynchronizer,
    ) {}

    public function __invoke(AnswerOrganizationalProviderContributionEmissionsRequest $request): void
    {
        $authentication = $this->authenticator->authenticate($request->token);
        $contribution = $authentication->contribution;
        $providerId = $contribution->providerId();

        if (!$providerId instanceof ProviderId) {
            throw new OrganizationalProviderContributionTokenInvalidException();
        }

        $located = $this->authenticator->locateCarbonFootprint($authentication);

        $located->ensureIsEditable();

        if (!$contribution->hasConsent()) {
            throw new OrganizationalProviderContributionConsentRequiredException();
        }

        $providerSections = Scope::Scope3->providerSectionsFor($contribution->sources());

        $sectionsByKey = [];
        foreach ($providerSections as $section) {
            $sectionsByKey[$section->key] = $section;
        }

        $sectionAnswers = [];
        foreach ($request->sectionAnswers as $sectionAnswer) {
            $definition = $sectionsByKey[$sectionAnswer->definition->key]
                ?? throw new \InvalidArgumentException(sprintf('Section "%s" is not covered by the provider contribution.', $sectionAnswer->definition->key));

            foreach ($sectionAnswer->answers as $answer) {
                if ($answer->type() !== $definition->activityType) {
                    throw new \InvalidArgumentException(sprintf('Answer of type "%s" does not match section "%s" type.', $answer->type()->value, $definition->key));
                }
            }

            $sectionAnswers[] = new SectionAnswer($definition, $sectionAnswer->answers);
        }

        $this->answersSynchronizer->sync(
            $located->carbonFootprint,
            Scope::Scope3,
            $sectionAnswers,
            $authentication->user,
            $providerSections,
            $providerId,
            false,
            $contribution->propertyId(),
        );

        $this->locator->save($located);

        $contribution->answer();

        $this->contributionRepository->add($contribution);
    }
}
