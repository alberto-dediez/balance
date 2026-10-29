<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AnswerCommuting;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionAuthenticator;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionClosedException;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionTokenInvalidException;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Model\Consent\ConsentProof;
use App\Shared\Domain\Model\Locale\LocaleGetter;

final class AnswerOrganizationalEmployeeContributionCommutingService
{
    public function __construct(
        private OrganizationalEmployeeContributionAuthenticator $authenticator,
        private OrganizationalEmployeeContributionRepository $contributionRepository,
        private OrganizationalCarbonFootprintLocator $locator,
        private CompanyProperties $companyProperties,
        private string $consentPolicyVersion,
    ) {}

    public function __invoke(AnswerOrganizationalEmployeeContributionCommutingRequest $request): void
    {
        $contribution = $this->authenticator->authenticate($request->token)->contribution;

        try {
            $carbonFootprint = $this->locator->locate($contribution->subjectType(), $contribution->carbonFootprintId())->carbonFootprint;
        } catch (\InvalidArgumentException) {
            throw new OrganizationalEmployeeContributionTokenInvalidException();
        }

        if ($carbonFootprint->isCompleted()) {
            throw new OrganizationalEmployeeContributionClosedException();
        }

        $this->ensureValidWorkplace($carbonFootprint, $request->workplacePropertyId);

        if ($request->consentGranted && !$contribution->hasConsent()) {
            $contribution->grantConsent(new ConsentProof(
                consentedAt: new \DateTimeImmutable('NOW'),
                policyVersion: $this->consentPolicyVersion,
                locale: LocaleGetter::locale()->value,
                ip: $request->consentIp,
                userAgent: $request->consentUserAgent,
                acceptLanguage: $request->consentAcceptLanguage,
            ));
        }

        $contribution->answerCommuting($request->workplacePropertyId, $request->weeksPerYear, $request->commutes);

        $this->contributionRepository->add($contribution);
    }

    private function ensureValidWorkplace(PropertyCarbonFootprint|CompanyCarbonFootprint $carbonFootprint, ?PropertyId $workplacePropertyId): void
    {
        if (null === $workplacePropertyId) {
            return;
        }

        if (!$carbonFootprint instanceof CompanyCarbonFootprint) {
            throw new \InvalidArgumentException('A property carbon footprint does not accept a workplace');
        }

        foreach ($this->companyProperties->consolidated($carbonFootprint->companyId()) as $property) {
            if ($property->id()->equals($workplacePropertyId)) {
                return;
            }
        }

        throw new \InvalidArgumentException('Property is not consolidated in the company');
    }
}
