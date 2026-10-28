<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\AnswerGeneralInfo;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\Shared\Domain\Model\Consent\ConsentProof;
use App\Shared\Domain\Model\Locale\LocaleGetter;

final class AnswerOrganizationalProviderContributionGeneralInfoService
{
    public function __construct(
        private OrganizationalProviderContributionAuthenticator $authenticator,
        private OrganizationalProviderContributionRepository $contributionRepository,
        private string $consentPolicyVersion,
    ) {}

    public function __invoke(AnswerOrganizationalProviderContributionGeneralInfoRequest $request): void
    {
        $authentication = $this->authenticator->authenticate($request->token);
        $contribution = $authentication->contribution;

        $this->authenticator->locateCarbonFootprint($authentication)->ensureIsEditable();

        $contribution->grantConsent(new ConsentProof(
            consentedAt: new \DateTimeImmutable('NOW'),
            policyVersion: $this->consentPolicyVersion,
            locale: LocaleGetter::locale()->value,
            ip: $request->consentIp,
            userAgent: $request->consentUserAgent,
            acceptLanguage: $request->consentAcceptLanguage,
        ));

        $contribution->updateGeneralInfo(
            companyName: $request->companyName,
            service: $request->service,
        );

        $this->contributionRepository->add($contribution);
    }
}
