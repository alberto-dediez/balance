<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprint\JustifiedExclusions;

use App\CarbonFootprint\Application\CarbonFootprint\JustifiedExclusions\DownloadJustifiedExclusionsDocumentRequest;
use App\CarbonFootprint\Application\CarbonFootprint\JustifiedExclusions\DownloadJustifiedExclusionsDocumentService;
use App\CarbonFootprint\Application\CarbonFootprint\JustifiedExclusions\JustifiedExclusionsRequireCompletedFootprintException;
use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportSubjects;
use App\CarbonFootprint\Application\CarbonFootprintReport\Data\CarbonFootprintReportDataCollectorRegistry;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Reopen\ReopenCompanyCarbonFootprintService;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\Reopen\ReopenEventCarbonFootprintService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Reopen\ReopenPropertyCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Security\Application\Branding\Resolve\ResolveBrandingService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Branding\BrandingPalette;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\GenerateTexts\StubCarbonFootprintReportDataCollector;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use App\Tests\Unit\Shared\Application\CurrentRoles\StubCurrentRolesProvider;
use App\Tests\Unit\Shared\Infrastructure\Theme\ThemeRegistryMother;
use PHPUnit\Framework\TestCase;

final class DownloadJustifiedExclusionsDocumentServiceTest extends TestCase
{
    private const string ACCOUNT_ID = 'account-id';
    private const string PROPERTY_FOOTPRINT_ID = 'property-carbon-footprint-id';
    private const string COMPANY_FOOTPRINT_ID = 'company-carbon-footprint-id';

    private InMemoryPropertyCarbonFootprintRepository $propertyCarbonFootprints;
    private InMemoryCompanyCarbonFootprintRepository $companyCarbonFootprints;
    private SpyJustifiedExclusionsDocumentPdfGenerator $pdfGenerator;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        LocaleGetter::setLocale(Locale::Spanish);

        $this->propertyCarbonFootprints = new InMemoryPropertyCarbonFootprintRepository();
        $this->companyCarbonFootprints = new InMemoryCompanyCarbonFootprintRepository();
        $this->pdfGenerator = new SpyJustifiedExclusionsDocumentPdfGenerator();

        $propertyCarbonFootprint = PropertyCarbonFootprintMother::withEmployeesAnswered('property-id', self::PROPERTY_FOOTPRINT_ID, self::ACCOUNT_ID);
        $propertyCarbonFootprint->complete(new Emissions());
        $this->propertyCarbonFootprints->add($propertyCarbonFootprint);

        $companyCarbonFootprint = CompanyCarbonFootprintMother::withEmployeesAnswered('company-id', self::COMPANY_FOOTPRINT_ID, self::ACCOUNT_ID);
        $companyCarbonFootprint->complete(new Emissions());
        $this->companyCarbonFootprints->add($companyCarbonFootprint);
    }

    public function testTheOwnerGetsThePathAndTheNameOfThePropertyDocument(): void
    {
        $response = $this->service()(new DownloadJustifiedExclusionsDocumentRequest(CarbonFootprintReportSubjectType::Property, self::PROPERTY_FOOTPRINT_ID), $this->owner(self::ACCOUNT_ID));

        $this->assertSame('/tmp/justified-exclusions.pdf', $response->path);
        $this->assertSame(sprintf('exclusiones-justificadas-%s-2025-es.pdf', self::PROPERTY_FOOTPRINT_ID), $response->filename);
        $data = $this->pdfGenerator->data;
        $this->assertNotNull($data);
        $this->assertSame(Locale::Spanish, $this->pdfGenerator->locale);
        $this->assertSame($data->subjectName, $this->pdfGenerator->branding?->documentAuthor);
    }

    public function testTheOwnerGetsTheCompanyDocument(): void
    {
        $response = $this->service()(new DownloadJustifiedExclusionsDocumentRequest(CarbonFootprintReportSubjectType::Company, self::COMPANY_FOOTPRINT_ID), $this->owner(self::ACCOUNT_ID));

        $this->assertStringStartsWith(sprintf('exclusiones-justificadas-%s-', self::COMPANY_FOOTPRINT_ID), $response->filename);
    }

    public function testAnOwnerImpersonatingTheAccountGetsTheDocument(): void
    {
        $response = $this->service(new AccountId(self::ACCOUNT_ID))(new DownloadJustifiedExclusionsDocumentRequest(CarbonFootprintReportSubjectType::Property, self::PROPERTY_FOOTPRINT_ID), $this->owner('another-account-id'));

        $this->assertSame('/tmp/justified-exclusions.pdf', $response->path);
    }

    public function testAnOwnerOfAnotherAccountCannotDownloadIt(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->service()(new DownloadJustifiedExclusionsDocumentRequest(CarbonFootprintReportSubjectType::Property, self::PROPERTY_FOOTPRINT_ID), $this->owner('another-account-id'));
    }

    public function testAnUnknownFootprintIsNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service()(new DownloadJustifiedExclusionsDocumentRequest(CarbonFootprintReportSubjectType::Property, 'unknown-footprint-id'), $this->owner(self::ACCOUNT_ID));
    }

    public function testAnOpenFootprintIsRejected(): void
    {
        $this->propertyCarbonFootprints->add(PropertyCarbonFootprintMother::withEmployeesAnswered('property-id', 'open-property-carbon-footprint-id', self::ACCOUNT_ID));

        $this->expectException(JustifiedExclusionsRequireCompletedFootprintException::class);

        $this->service()(new DownloadJustifiedExclusionsDocumentRequest(CarbonFootprintReportSubjectType::Property, 'open-property-carbon-footprint-id'), $this->owner(self::ACCOUNT_ID));
    }

    public function testAnOpenCompanyFootprintIsRejected(): void
    {
        $this->companyCarbonFootprints->add(CompanyCarbonFootprintMother::withEmployeesAnswered('company-id', 'open-company-carbon-footprint-id', self::ACCOUNT_ID));

        $this->expectException(JustifiedExclusionsRequireCompletedFootprintException::class);

        $this->service()(new DownloadJustifiedExclusionsDocumentRequest(CarbonFootprintReportSubjectType::Company, 'open-company-carbon-footprint-id'), $this->owner(self::ACCOUNT_ID));
    }

    public function testAnEventFootprintIsNotSupported(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service()(new DownloadJustifiedExclusionsDocumentRequest(CarbonFootprintReportSubjectType::Event, self::PROPERTY_FOOTPRINT_ID), $this->owner(self::ACCOUNT_ID));
    }

    private function owner(string $accountId): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner-' . $accountId, Role::Owner, new AccountId($accountId));
    }

    private function service(?AccountId $impersonatedAccountId = null): DownloadJustifiedExclusionsDocumentService
    {
        $eventCarbonFootprints = new InMemoryEventCarbonFootprintRepository();
        $propertyAuthorizer = null === $impersonatedAccountId
            ? new PropertyCarbonFootprintAuthorizer()
            : new PropertyCarbonFootprintAuthorizer(new StubCurrentAccountIdProvider($impersonatedAccountId), new StubCurrentRolesProvider([Role::Owner]));

        return new DownloadJustifiedExclusionsDocumentService(
            new CarbonFootprintReportSubjects(
                $eventCarbonFootprints,
                $this->propertyCarbonFootprints,
                $this->companyCarbonFootprints,
                new ReopenEventCarbonFootprintService($eventCarbonFootprints, new EventCarbonFootprintAuthorizer()),
                new ReopenPropertyCarbonFootprintService($this->propertyCarbonFootprints, new PropertyCarbonFootprintAuthorizer()),
                new ReopenCompanyCarbonFootprintService($this->companyCarbonFootprints, new CompanyCarbonFootprintAuthorizer()),
            ),
            $propertyAuthorizer,
            new CompanyCarbonFootprintAuthorizer(),
            new CarbonFootprintReportDataCollectorRegistry([new StubCarbonFootprintReportDataCollector()]),
            new ResolveBrandingService(BrandingRepositoryMother::inMemory(), ThemeRegistryMother::withThemes(), new BrandingPalette()),
            $this->pdfGenerator,
        );
    }
}
