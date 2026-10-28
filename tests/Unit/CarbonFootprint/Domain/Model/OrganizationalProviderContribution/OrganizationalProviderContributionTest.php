<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionAnswered;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionId;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRegistered;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRemoved;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionSourcesChanged;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\ProviderContributionMode;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Domain\Model\Consent\ConsentProofMother;
use PHPUnit\Framework\TestCase;

class OrganizationalProviderContributionTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testManualIsBornAnswered(): void
    {
        $contribution = OrganizationalProviderContributionMother::manual(sources: [Source::UpstreamTransport, Source::OperationsWaste]);

        $this->assertInstanceOf(OrganizationalProviderContributionRegistered::class, $this->spySubscriber->lastEvent());
        $this->assertSame(ProviderContributionStatus::Answered, $contribution->status());
        $this->assertNotNull($contribution->answeredAt());
        $this->assertSame('', $contribution->token());
        $this->assertTrue($contribution->isManual());
        $this->assertSame([Source::UpstreamTransport, Source::OperationsWaste], $contribution->sources());
        $this->assertTrue($contribution->covers(Source::OperationsWaste));
        $this->assertFalse($contribution->covers(Source::CapitalGoods));
    }

    public function testRequestedIsBornPendingWithToken(): void
    {
        $contribution = OrganizationalProviderContributionMother::requested();

        $this->assertSame(ProviderContributionStatus::Pending, $contribution->status());
        $this->assertNull($contribution->answeredAt());
        $this->assertSame(64, strlen($contribution->token()));
        $this->assertTrue($contribution->isRequested());
    }

    public function testRequestedWithoutTokenThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->register(ProviderContributionMode::Requested, [Source::UpstreamTransport], '');
    }

    public function testRegisterWithoutSourcesThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->register(ProviderContributionMode::Manual, []);
    }

    public function testRegisterWithSourceOutsideProviderSourcesThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->register(ProviderContributionMode::Manual, [Source::Investments]);
    }

    public function testChangeSources(): void
    {
        $contribution = OrganizationalProviderContributionMother::manual(sources: [Source::UpstreamTransport, Source::OperationsWaste]);

        $contribution->changeSources([Source::BusinessTravel, Source::UpstreamTransport]);

        $this->assertInstanceOf(OrganizationalProviderContributionSourcesChanged::class, $this->spySubscriber->lastEvent());
        $this->assertSame([Source::UpstreamTransport, Source::BusinessTravel], $contribution->sources());
    }

    public function testChangeSourcesToEmptyThrows(): void
    {
        $contribution = OrganizationalProviderContributionMother::manual();

        $this->expectException(\InvalidArgumentException::class);

        $contribution->changeSources([]);
    }

    public function testAnswer(): void
    {
        $contribution = OrganizationalProviderContributionMother::requested();

        $contribution->answer();

        $this->assertInstanceOf(OrganizationalProviderContributionAnswered::class, $this->spySubscriber->lastEvent());
        $this->assertSame(ProviderContributionStatus::Answered, $contribution->status());
        $this->assertNotNull($contribution->answeredAt());
    }

    public function testIgnoreAndUnignoreBackToPending(): void
    {
        $contribution = OrganizationalProviderContributionMother::requested();

        $contribution->ignore();
        $this->assertSame(ProviderContributionStatus::Ignored, $contribution->status());
        $this->assertNotNull($contribution->ignoredAt());

        $contribution->unignore();
        $this->assertSame(ProviderContributionStatus::Pending, $contribution->status());
        $this->assertNull($contribution->ignoredAt());
    }

    public function testUnignoreBackToAnsweredWhenAnswered(): void
    {
        $contribution = OrganizationalProviderContributionMother::manual();

        $contribution->ignore();
        $contribution->unignore();

        $this->assertSame(ProviderContributionStatus::Answered, $contribution->status());
    }

    public function testResendNotificationOnManualThrows(): void
    {
        $contribution = OrganizationalProviderContributionMother::manual();

        $this->expectException(\LogicException::class);

        $contribution->resendNotification();
    }

    public function testResendNotificationOnIgnoredThrows(): void
    {
        $contribution = OrganizationalProviderContributionMother::requested();
        $contribution->ignore();

        $this->expectException(\LogicException::class);

        $contribution->resendNotification();
    }

    public function testUpdateGeneralInfoAndGrantConsent(): void
    {
        $contribution = OrganizationalProviderContributionMother::requested();

        $this->assertFalse($contribution->hasConsent());

        $contribution->grantConsent(ConsentProofMother::granted());
        $contribution->updateGeneralInfo('Transportes Norte', 'Reparto');

        $this->assertTrue($contribution->hasConsent());
        $this->assertSame('Transportes Norte', $contribution->companyName());
        $this->assertSame('Reparto', $contribution->service());
    }

    public function testRemove(): void
    {
        $contribution = OrganizationalProviderContributionMother::manual();

        $contribution->remove();

        $this->assertInstanceOf(OrganizationalProviderContributionRemoved::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($contribution->isRemoved());
        $this->assertNotNull($contribution->removedAt());
    }

    /** @param list<Source> $sources */
    private function register(ProviderContributionMode $mode, array $sources, string $token = ''): OrganizationalProviderContribution
    {
        $contribution = new OrganizationalProviderContribution(OrganizationalProviderContributionId::generate());
        $contribution->register(
            subjectType: CarbonFootprintReportSubjectType::Property,
            carbonFootprintId: 'footprint-id',
            accountId: new AccountId('account-id'),
            providerId: new ProviderId('provider-id'),
            propertyId: null,
            mode: $mode,
            token: $token,
            sources: $sources,
            companyName: null,
        );

        return $contribution;
    }
}
