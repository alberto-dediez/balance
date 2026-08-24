<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\AnswerGeneralInfo;

use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionRepository;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionTokenInvalidException;
use App\Shared\Domain\Model\Consent\ConsentProof;
use App\Shared\Domain\Model\Locale\LocaleGetter;

final class AnswerProviderContributionGeneralInfoService
{
    public function __construct(
        private ProviderContributionAuthenticator $authenticator,
        private ProviderContributionRepository $providerContributionRepository,
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private string $consentPolicyVersion,
    ) {}

    public function __invoke(AnswerProviderContributionGeneralInfoRequest $request): void
    {
        $authentication = $this->authenticator->authenticate($request->token);
        $providerContribution = $authentication->providerContribution;

        $eventCarbonFootprintId = $providerContribution->eventCarbonFootprintId();
        $eventCarbonFootprint = null !== $eventCarbonFootprintId ? $this->eventCarbonFootprintRepository->find($eventCarbonFootprintId) : null;

        if (null === $eventCarbonFootprint) {
            throw new ProviderContributionTokenInvalidException();
        }

        if ($eventCarbonFootprint->status()->isCompleted()) {
            throw new ProviderContributionClosedException();
        }

        $providerContribution->grantConsent(new ConsentProof(
            consentedAt: new \DateTimeImmutable('NOW'),
            policyVersion: $this->consentPolicyVersion,
            locale: LocaleGetter::locale()->value,
            ip: $request->consentIp,
            userAgent: $request->consentUserAgent,
            acceptLanguage: $request->consentAcceptLanguage,
        ));

        $providerContribution->updateGeneralInfo(
            companyName: $request->companyName,
            companyType: $request->companyType,
            service: $request->service,
            workersAssembly: $request->workersAssembly,
            workersCelebration: $request->workersCelebration,
            workersBreakdown: $request->workersBreakdown,
        );

        $this->providerContributionRepository->add($providerContribution);
    }
}
