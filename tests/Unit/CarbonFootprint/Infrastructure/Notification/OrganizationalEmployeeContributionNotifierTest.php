<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Notification;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Notification\OrganizationalEmployeeContributionNotifier;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOrganizationalEmployeeContributionRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeRepository;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\Branding\Resolve\ResolveBrandingService;
use App\Security\Domain\Model\Branding\BrandingPalette;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\Notifications\Application\Notification\Deliver\SpyEmailSender;
use App\Tests\Unit\Notifications\Application\Notification\Notify\SpySystemNotifier;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use App\Tests\Unit\Shared\Infrastructure\Theme\ThemeRegistryMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\LocaleSwitcher;

class OrganizationalEmployeeContributionNotifierTest extends TestCase
{
    private const string FOOTPRINT_ID = 'footprint-1';

    private InMemoryOrganizationalEmployeeContributionRepository $contributionRepository;
    private InMemoryEmployeeRepository $employeeRepository;
    private InMemoryUserRepository $userRepository;
    private InMemoryPropertyCarbonFootprintRepository $propertyCarbonFootprintRepository;
    private SpyEmailSender $emailSender;
    private SpySystemNotifier $systemNotifier;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->contributionRepository = new InMemoryOrganizationalEmployeeContributionRepository();
        $this->employeeRepository = new InMemoryEmployeeRepository();
        $this->userRepository = new InMemoryUserRepository();
        $this->propertyCarbonFootprintRepository = new InMemoryPropertyCarbonFootprintRepository();
        $propertyRepository = new InMemoryPropertyRepository();
        $this->emailSender = new SpyEmailSender();
        $this->systemNotifier = new SpySystemNotifier();
        $translator = new FakeTranslator();

        $propertyId = PropertyId::generate();
        $propertyRepository->add(PropertyMother::from($propertyId, 'Hotel Sol'));
        $this->propertyCarbonFootprintRepository->add(PropertyCarbonFootprintMother::withProvidersAnswered((string) $propertyId, self::FOOTPRINT_ID, 'account-id', 2025));

        new OrganizationalEmployeeContributionNotifier(
            $this->emailSender,
            $this->systemNotifier,
            $translator,
            $this->contributionRepository,
            $this->employeeRepository,
            $this->userRepository,
            OrganizationalCarbonFootprintLocatorMother::create($this->propertyCarbonFootprintRepository),
            $propertyRepository,
            new InMemoryCompanyRepository(),
            new LocaleSwitcher('es', [$translator]),
            new ResolveBrandingService(BrandingRepositoryMother::inMemory(), ThemeRegistryMother::withThemes(), new BrandingPalette()),
        );
    }

    public function testRegisteringAContributionSendsAnEmailToTheEmployee(): void
    {
        $employee = EmployeeMother::forAccount('account-id');
        $this->employeeRepository->add($employee);

        OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, self::FOOTPRINT_ID, 'account-id', (string) $employee->id());

        $sent = $this->emailSender->sent();
        $this->assertCount(1, $sent);
        $this->assertSame($employee->email(), $sent[0]['recipient']->email);
        $this->assertSame('@carbon_footprint/emails/organizational_employee_contribution_requested.html.twig', $sent[0]['template']);
        $this->assertSame('Hotel Sol', $sent[0]['params']['subjectName'] ?? null);
        $this->assertSame(2025, $sent[0]['params']['year'] ?? null);
        $this->assertCount(0, $this->systemNotifier->notifications());
    }

    public function testAnEmployeeWithALinkedUserAlsoReceivesAnInternalNotification(): void
    {
        $userId = (string) UserId::generate();
        $employee = EmployeeMother::withUserId((string) EmployeeId::generate(), $userId);
        $this->employeeRepository->add($employee);
        $this->userRepository->add(UserMother::fromUsernameAndRoleAndId('employee-user', Role::User, new UserId($userId)));

        OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, self::FOOTPRINT_ID, 'account-id', (string) $employee->id());

        $this->assertCount(1, $this->emailSender->sent());

        $notifications = $this->systemNotifier->notifications();
        $this->assertCount(1, $notifications);
        $this->assertSame(NotificationChannel::Internal, $notifications[0]->channel);
        $this->assertTrue($notifications[0]->recipientId->equals(new UserId($userId)));
    }

    public function testResendingSendsTheEmailAgain(): void
    {
        $employee = EmployeeMother::forAccount('account-id');
        $this->employeeRepository->add($employee);

        $contribution = OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, self::FOOTPRINT_ID, 'account-id', (string) $employee->id());
        $this->contributionRepository->add($contribution);

        $contribution->resendNotification();

        $this->assertCount(2, $this->emailSender->sent());
    }

    public function testAMissingEmployeeSendsNothing(): void
    {
        OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, self::FOOTPRINT_ID, 'account-id');

        $this->assertCount(0, $this->emailSender->sent());
        $this->assertCount(0, $this->systemNotifier->notifications());
    }

    public function testAMissingFootprintSendsNothing(): void
    {
        $employee = EmployeeMother::forAccount('account-id');
        $this->employeeRepository->add($employee);

        OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, (string) PropertyCarbonFootprintId::generate(), 'account-id', (string) $employee->id());

        $this->assertCount(0, $this->emailSender->sent());
    }
}
