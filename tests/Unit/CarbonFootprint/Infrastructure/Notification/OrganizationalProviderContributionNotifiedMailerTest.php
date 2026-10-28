<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Notification;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Notification\OrganizationalProviderContributionNotifiedMailer;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOrganizationalProviderContributionRepository;
use App\EventOperations\Domain\Model\Provider\Provider;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\Branding\Resolve\ResolveBrandingService;
use App\Security\Domain\Model\Branding\BrandingPalette;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderRepositoryMother;
use App\Tests\Unit\Notifications\Application\Notification\Deliver\SpyEmailSender;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingRepositoryMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use App\Tests\Unit\Shared\Infrastructure\Theme\ThemeRegistryMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\LocaleSwitcher;

class OrganizationalProviderContributionNotifiedMailerTest extends TestCase
{
    private const string ACCOUNT_ID = 'account-id';

    private InMemoryOrganizationalProviderContributionRepository $contributionRepository;
    private InMemoryProviderRepository $providerRepository;
    private InMemoryPropertyRepository $propertyRepository;
    private SpyEmailSender $emailSender;
    private PropertyCarbonFootprint $footprint;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->contributionRepository = OrganizationalProviderContributionRepositoryMother::inMemory();
        $this->providerRepository = ProviderRepositoryMother::inMemory();
        $this->propertyRepository = PropertyRepositoryMother::inMemory();
        $propertyCarbonFootprintRepository = PropertyCarbonFootprintRepositoryMother::inMemory();
        $this->emailSender = new SpyEmailSender();
        $translator = new FakeTranslator();

        $propertyId = (string) PropertyId::generate();
        $this->propertyRepository->add(PropertyMother::fromNameAndAccountAndId('Hotel Sol', self::ACCOUNT_ID, $propertyId));
        $this->footprint = PropertyCarbonFootprintMother::withScope3Answered($propertyId, (string) PropertyCarbonFootprintId::generate(), self::ACCOUNT_ID);
        $propertyCarbonFootprintRepository->add($this->footprint);

        new OrganizationalProviderContributionNotifiedMailer(
            $this->emailSender,
            $translator,
            $this->contributionRepository,
            $this->providerRepository,
            OrganizationalCarbonFootprintLocatorMother::create($propertyCarbonFootprintRepository),
            $this->propertyRepository,
            CompanyRepositoryMother::inMemory(),
            new LocaleSwitcher('es', [$translator]),
            new ResolveBrandingService(BrandingRepositoryMother::inMemory(), ThemeRegistryMother::withThemes(), new BrandingPalette()),
        );
    }

    public function testRegisteringARequestedContributionSendsAnEmailToTheProvider(): void
    {
        $provider = $this->addProvider(ProviderMother::fromNameAndAccountAndId('Logística Sur', self::ACCOUNT_ID, (string) ProviderId::generate()));

        $contribution = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $this->footprint->id(), providerId: (string) $provider->id());

        $sent = $this->emailSender->sent();
        $this->assertCount(1, $sent);
        $this->assertSame($provider->contact()->email(), $sent[0]['recipient']->email);
        $this->assertSame('@carbon_footprint/emails/organizational_provider_contribution_requested.html.twig', $sent[0]['template']);
        $this->assertSame(sprintf('Aporta tus datos de emisiones de %d para Hotel Sol', $this->footprint->year()), $sent[0]['subject']);
        $this->assertSame($contribution->token(), $sent[0]['params']['token'] ?? null);
    }

    public function testRegisteringAManualContributionSendsNothing(): void
    {
        $provider = $this->addProvider(ProviderMother::fromNameAndAccountAndId('Logística Sur', self::ACCOUNT_ID, (string) ProviderId::generate()));

        OrganizationalProviderContributionMother::manual(carbonFootprintId: (string) $this->footprint->id(), providerId: (string) $provider->id());

        $this->assertCount(0, $this->emailSender->sent());
    }

    public function testResendingSendsTheEmailAgain(): void
    {
        $provider = $this->addProvider(ProviderMother::fromNameAndAccountAndId('Logística Sur', self::ACCOUNT_ID, (string) ProviderId::generate()));
        $contribution = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $this->footprint->id(), providerId: (string) $provider->id());
        $this->contributionRepository->add($contribution);

        $contribution->resendNotification();

        $this->assertCount(2, $this->emailSender->sent());
    }

    public function testResendingToAProviderWithoutEmailSendsNothing(): void
    {
        $provider = $this->addProvider(ProviderMother::withoutEmail('Logística Sur', self::ACCOUNT_ID, (string) ProviderId::generate()));
        $contribution = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $this->footprint->id(), providerId: (string) $provider->id());
        $this->contributionRepository->add($contribution);

        $contribution->resendNotification();

        $this->assertCount(0, $this->emailSender->sent());
    }

    private function addProvider(Provider $provider): Provider
    {
        $this->providerRepository->add($provider);

        return $provider;
    }
}
