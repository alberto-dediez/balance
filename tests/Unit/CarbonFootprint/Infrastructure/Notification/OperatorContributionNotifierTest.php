<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Notification;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Notification\OperatorContributionNotifier;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOperatorContributionRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\Branding\Resolve\ResolveBrandingService;
use App\Security\Domain\Model\Branding\BrandingPalette;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionMother;
use App\Tests\Unit\Notifications\Application\Notification\Deliver\SpyEmailSender;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingRepositoryMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use App\Tests\Unit\Shared\Infrastructure\Theme\ThemeRegistryMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\LocaleSwitcher;

class OperatorContributionNotifierTest extends TestCase
{
    private const string FOOTPRINT_ID = 'company-footprint-1';

    private InMemoryOperatorContributionRepository $contributionRepository;
    private SpyEmailSender $emailSender;
    private LocaleSwitcher $localeSwitcher;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->contributionRepository = new InMemoryOperatorContributionRepository();
        $this->emailSender = new SpyEmailSender();
        $translator = new FakeTranslator();
        $this->localeSwitcher = new LocaleSwitcher('es', [$translator]);

        $propertyRepository = new InMemoryPropertyRepository();
        $propertyRepository->add(PropertyMother::from(new PropertyId('property-1'), 'Hotel Sol'));

        $companyRepository = new InMemoryCompanyRepository();
        $companyRepository->add(CompanyMother::fromNameAndId('Hotelera', 'company-1'));

        $companyCarbonFootprintRepository = CompanyCarbonFootprintRepositoryMother::inMemory();
        $companyCarbonFootprintRepository->add(CompanyCarbonFootprintMother::withScope3Answered('company-1', self::FOOTPRINT_ID, 'account-id', 2025));

        new OperatorContributionNotifier(
            $this->emailSender,
            $translator,
            $this->contributionRepository,
            $companyCarbonFootprintRepository,
            $propertyRepository,
            $companyRepository,
            $this->localeSwitcher,
            new ResolveBrandingService(BrandingRepositoryMother::inMemory(), ThemeRegistryMother::withThemes(), new BrandingPalette()),
        );
    }

    public function testRegisteringAContributionSendsAnEmailToTheContact(): void
    {
        $contribution = OperatorContributionMother::pending(companyCarbonFootprintId: self::FOOTPRINT_ID, propertyId: 'property-1', contactEmail: 'operador@hotel.es');

        $sent = $this->emailSender->sent();
        $this->assertCount(1, $sent);
        $this->assertSame('operador@hotel.es', $sent[0]['recipient']->email);
        $this->assertSame('@carbon_footprint/emails/operator_contribution_requested.html.twig', $sent[0]['template']);
        $this->assertSame('Hotel Sol', $sent[0]['params']['propertyName'] ?? null);
        $this->assertSame('Hotelera', $sent[0]['params']['companyName'] ?? null);
        $this->assertSame(2025, $sent[0]['params']['year'] ?? null);
        $this->assertSame($contribution->token(), $sent[0]['params']['token'] ?? null);
    }

    public function testResendingSendsTheEmailAgain(): void
    {
        $contribution = OperatorContributionMother::pending(companyCarbonFootprintId: self::FOOTPRINT_ID, propertyId: 'property-1');
        $this->contributionRepository->add($contribution);

        $contribution->resendNotification();

        $this->assertCount(2, $this->emailSender->sent());
    }

    public function testAMissingFootprintSendsNothing(): void
    {
        OperatorContributionMother::pending(companyCarbonFootprintId: (string) CompanyCarbonFootprintId::generate(), propertyId: 'property-1');

        $this->assertCount(0, $this->emailSender->sent());
    }

    public function testTheLocaleIsRestoredAfterSending(): void
    {
        OperatorContributionMother::pending(companyCarbonFootprintId: self::FOOTPRINT_ID, propertyId: 'property-1');

        $this->assertSame('es', $this->localeSwitcher->getLocale());
    }
}
