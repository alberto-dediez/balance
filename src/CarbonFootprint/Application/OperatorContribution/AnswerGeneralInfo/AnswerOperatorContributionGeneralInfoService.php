<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\AnswerGeneralInfo;

use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionActivitiesRemover;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRepository;
use App\Shared\Domain\Model\Consent\ConsentProof;
use App\Shared\Domain\Model\Locale\LocaleGetter;

final class AnswerOperatorContributionGeneralInfoService
{
    public function __construct(
        private OperatorContributionAuthenticator $authenticator,
        private OperatorContributionRepository $contributionRepository,
        private OperatorContributionActivitiesRemover $activitiesRemover,
        private string $consentPolicyVersion,
    ) {}

    public function __invoke(AnswerOperatorContributionGeneralInfoRequest $request): void
    {
        $authentication = $this->authenticator->authenticate($request->token);
        $contribution = $authentication->contribution;

        $this->authenticator->ensureEditable($this->authenticator->locateCompanyCarbonFootprint($authentication));

        $contribution->grantConsent(new ConsentProof(
            consentedAt: new \DateTimeImmutable('NOW'),
            policyVersion: $this->consentPolicyVersion,
            locale: LocaleGetter::locale()->value,
            ip: $request->consentIp,
            userAgent: $request->consentUserAgent,
            acceptLanguage: $request->consentAcceptLanguage,
        ));

        $contribution->updateContactName($request->contactName);

        if ($request->precalculated !== $contribution->isPrecalculated()) {
            $this->activitiesRemover->removeAll($contribution);

            if ($request->precalculated) {
                $contribution->markAsPrecalculated();
            } else {
                $contribution->unmarkAsPrecalculated();
            }
        }

        $this->contributionRepository->add($contribution);
    }
}
