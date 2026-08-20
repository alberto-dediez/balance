<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Notification;

use App\CarbonFootprint\Infrastructure\Notification\EmployeeContributionNotifier;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeContributionRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeRepository;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Application\Branding\Resolve\ResolveBrandingService;
use App\Security\Domain\Model\Branding\BrandingPalette;
use App\Security\Domain\Model\Branding\BrandingTokens;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryBrandingRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\Notifications\Application\Notification\Deliver\SpyEmailSender;
use App\Tests\Unit\Notifications\Application\Notification\Notify\SpySystemNotifier;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use App\Tests\Unit\Shared\Infrastructure\Theme\ThemeRegistryMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\LocaleSwitcher;

class EmployeeContributionNotifierTest extends TestCase
{
    private InMemoryEmployeeContributionRepository $employeeContributionRepository;
    private InMemoryEmployeeRepository $employeeRepository;
    private InMemoryUserRepository $userRepository;
    private SpyEmailSender $emailSender;
    private SpySystemNotifier $systemNotifier;
    private InMemoryBrandingRepository $brandingRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->employeeContributionRepository = new InMemoryEmployeeContributionRepository();
        $this->employeeRepository = new InMemoryEmployeeRepository();
        $this->userRepository = new InMemoryUserRepository();
        $this->emailSender = new SpyEmailSender();
        $this->systemNotifier = new SpySystemNotifier();
        $translator = new FakeTranslator();
        $this->brandingRepository = BrandingRepositoryMother::inMemory();

        new EmployeeContributionNotifier(
            $this->emailSender,
            $this->systemNotifier,
            $translator,
            $this->employeeContributionRepository,
            $this->employeeRepository,
            $this->userRepository,
            new LocaleSwitcher('es', [$translator]),
            new ResolveBrandingService($this->brandingRepository, ThemeRegistryMother::withThemes(), new BrandingPalette()),
        );
    }

    public function testRegisteringAContributionSendsAnEmailToTheEmployee(): void
    {
        $employee = EmployeeMother::forAccount('account-id');
        $this->employeeRepository->add($employee);

        EmployeeContributionMother::fromEventCarbonFootprintIdAndEmployeeId(
            (string) EventCarbonFootprintMother::random()->id(),
            (string) $employee->id(),
        );

        $sent = $this->emailSender->sent();
        $this->assertCount(1, $sent);
        $this->assertSame($employee->email(), $sent[0]['recipient']->email);
        $this->assertSame('@carbon_footprint/emails/employee_contribution_requested.html.twig', $sent[0]['template']);

        $this->assertCount(0, $this->systemNotifier->notifications());
    }

    public function testRegisteringAContributionForAnEmployeeWithLinkedUserAlsoSendsAnInternalNotification(): void
    {
        $userId = (string) UserId::generate();
        $employee = EmployeeMother::withUserId((string) EmployeeId::generate(), $userId);
        $this->employeeRepository->add($employee);
        $this->userRepository->add(UserMother::fromUsernameAndRoleAndId('employee-user', Role::User, new UserId($userId)));

        EmployeeContributionMother::fromEventCarbonFootprintIdAndEmployeeId(
            (string) EventCarbonFootprintMother::random()->id(),
            (string) $employee->id(),
        );

        $this->assertCount(1, $this->emailSender->sent());

        $notifications = $this->systemNotifier->notifications();
        $this->assertCount(1, $notifications);
        $this->assertSame(NotificationChannel::Internal, $notifications[0]->channel);
        $this->assertTrue($notifications[0]->recipientId->equals(new UserId($userId)));
    }

    public function testResendingANotificationSendsTheEmailAgain(): void
    {
        $employee = EmployeeMother::forAccount('account-id');
        $this->employeeRepository->add($employee);

        $contribution = EmployeeContributionMother::fromEventCarbonFootprintIdAndEmployeeId(
            (string) EventCarbonFootprintMother::random()->id(),
            (string) $employee->id(),
        );
        $this->employeeContributionRepository->add($contribution);

        $contribution->resendNotification();

        $this->assertCount(2, $this->emailSender->sent());
    }

    public function testTheEmailCarriesTheBrandingOfTheEmployeeAccount(): void
    {
        $employee = EmployeeMother::forAccount('account-id');
        $this->employeeRepository->add($employee);

        $this->brandingRepository->add(BrandingMother::brandedForAccount($employee->accountId()));

        EmployeeContributionMother::fromEventCarbonFootprintIdAndEmployeeId(
            (string) EventCarbonFootprintMother::random()->id(),
            (string) $employee->id(),
        );

        $params = $this->emailSender->sent()[0]['params'] ?? [];
        $tokens = $params['brandingTokens'] ?? null;

        $this->assertInstanceOf(BrandingTokens::class, $tokens);
        $this->assertSame('#8A2432', $tokens->primary->value);
    }

    public function testMissingEmployeeSendsNothing(): void
    {
        EmployeeContributionMother::registeredEmployeeContribution();

        $this->assertCount(0, $this->emailSender->sent());
        $this->assertCount(0, $this->systemNotifier->notifications());
    }
}
