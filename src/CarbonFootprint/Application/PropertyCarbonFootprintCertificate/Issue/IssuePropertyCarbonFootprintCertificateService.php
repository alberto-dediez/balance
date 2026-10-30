<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Issue;

use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Generate\GeneratePropertyCarbonFootprintCertificateRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintAlreadyCertifiedException;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintNotCertifiableException;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificate;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateCodeGenerator;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateVerificationTokenGenerator;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class IssuePropertyCarbonFootprintCertificateService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PropertyCarbonFootprintCertificateRepository $certificateRepository,
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private CarbonFootprintReportRepository $reportRepository,
        private PropertyRepository $propertyRepository,
        private AccountRepository $accountRepository,
        private PropertyCarbonFootprintAuthorizer $authorizer,
        private EmissionsBreakdownBuilder $breakdownBuilder,
        private PropertyCarbonFootprintCertificateCodeGenerator $codeGenerator,
        private PropertyCarbonFootprintCertificateVerificationTokenGenerator $verificationTokenGenerator,
    ) {}

    public function __invoke(IssuePropertyCarbonFootprintCertificateRequest $request, User $user): IssuePropertyCarbonFootprintCertificateResponse
    {
        $propertyCarbonFootprint = $this->propertyCarbonFootprintRepository->find($request->propertyCarbonFootprintId);

        if (null === $propertyCarbonFootprint) {
            throw new \InvalidArgumentException('PropertyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($propertyCarbonFootprint, SecurityAction::Edit, $user);

        $this->ensureCertifiable($propertyCarbonFootprint);

        $breakdown = $this->breakdownBuilder->build($propertyCarbonFootprint, Scope::cases());
        $scope1 = $breakdown->byStage[Scope::Scope1->key()] ?? new Emissions();
        $scope2 = $breakdown->byStage[Scope::Scope2->key()] ?? new Emissions();
        $scope3 = $breakdown->byStage[Scope::Scope3->key()] ?? new Emissions();

        $propertyId = $propertyCarbonFootprint->propertyId();
        $property = $propertyId instanceof PropertyId ? $this->propertyRepository->find($propertyId) : null;

        if (null === $propertyId || null === $property) {
            throw new \InvalidArgumentException('Property not found');
        }

        $accountId = $propertyCarbonFootprint->accountId();
        $account = $accountId instanceof AccountId ? $this->accountRepository->find($accountId) : null;

        if (null === $accountId || null === $account) {
            throw new \InvalidArgumentException('Account not found');
        }

        $certificate = new PropertyCarbonFootprintCertificate(PropertyCarbonFootprintCertificateId::generate());

        $certificate->issue(
            propertyCarbonFootprintId: $propertyCarbonFootprint->id(),
            propertyId: $propertyId,
            accountId: $accountId,
            year: $propertyCarbonFootprint->year(),
            code: $this->codeGenerator->generate(),
            verificationToken: $this->verificationTokenGenerator->generate(),
            locale: LocaleGetter::locale(),
            propertyName: $property->name(),
            accountName: $account->name(),
            stays: $request->stays ?? 0,
            totalCo2eqKg: (float) $propertyCarbonFootprint->totalEmissions()->co2eq(),
            scope1Co2eqKg: (float) $scope1->co2eq(),
            scope2Co2eqKg: (float) $scope2->co2eq(),
            scope3Co2eqKg: $propertyCarbonFootprint->scope3Excluded() ? null : (float) $scope3->co2eq(),
        );

        $this->certificateRepository->add($certificate);

        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(
                command: new GeneratePropertyCarbonFootprintCertificateRequest($certificate->id()),
                priority: Priority::LOW,
            ),
            $user,
        );

        return new IssuePropertyCarbonFootprintCertificateResponse(certificate: $certificate);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof IssuePropertyCarbonFootprintCertificateRequest;
    }

    private function ensureCertifiable(PropertyCarbonFootprint $propertyCarbonFootprint): void
    {
        if (!$propertyCarbonFootprint->isCompleted()) {
            throw new PropertyCarbonFootprintNotCertifiableException('Property carbon footprint is not completed');
        }

        $report = $this->reportRepository->findBySubject(CarbonFootprintReportSubjectType::Property, (string) $propertyCarbonFootprint->id());

        if (null === $report || !$report->status()->isGenerated()) {
            throw new PropertyCarbonFootprintNotCertifiableException('Property carbon footprint report is not generated');
        }

        if (null !== $this->certificateRepository->findCurrentByPropertyCarbonFootprintId($propertyCarbonFootprint->id())) {
            throw new PropertyCarbonFootprintAlreadyCertifiedException('Property carbon footprint already has a current certificate');
        }
    }
}
