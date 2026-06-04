<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Alert\Check;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Waste\Domain\Model\Alert\AlertMother;
use App\Tests\Unit\Waste\Domain\Model\Alert\AlertRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\AlertConfiguration\AlertConfigurationMother;
use App\Tests\Unit\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteRepositoryMother;
use App\Waste\Application\Alert\AlertAuthorizer;
use App\Waste\Application\Alert\Check\CheckAlertsRequest;
use App\Waste\Application\Alert\Check\CheckAlertsService;
use App\Waste\Application\Alert\Check\Checker\AlertChecker;
use App\Waste\Application\Alert\Check\DailyWasteWindowProvider;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\Alert\ExistingAlertsIndex;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItem;
use PHPUnit\Framework\TestCase;

class CheckAlertsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testReturnsZeroWhenNoConfigFound(): void
    {
        $alertRepository = AlertRepositoryMother::inMemory();
        $configRepository = AlertConfigurationRepositoryMother::inMemory();
        $dailyWasteWindowProvider = new DailyWasteWindowProvider(DailyWasteRepositoryMother::inMemory());

        $sut = new CheckAlertsService(
            alertRepository: $alertRepository,
            alertConfigurationRepository: $configRepository,
            dailyWasteWindowProvider: $dailyWasteWindowProvider,
            authorizer: new AlertAuthorizer(),
            checkers: [],
        );

        $result = $sut(
            new CheckAlertsRequest(
                checkDate: new \DateTimeImmutable('2026-06-01'),
                accountId: new AccountId('account-id-test'),
            ),
            UserMother::adminUser(),
        );

        $this->assertEquals(0, $result->detectedAlerts);
    }

    public function testReturnsZeroWhenNoEnabledRulesForAnyChecker(): void
    {
        $alertRepository = AlertRepositoryMother::inMemory();
        $configRepository = AlertConfigurationRepositoryMother::inMemory();
        $dailyWasteWindowProvider = new DailyWasteWindowProvider(DailyWasteRepositoryMother::inMemory());

        $accountId = new AccountId('account-id-test');
        $config = AlertConfigurationMother::from(accountId: 'account-id-test', noMeasurementsEnabled: false);
        $configRepository->add($config);

        $stubChecker = new class implements AlertChecker {
            public function type(): AlertType
            {
                return AlertType::NoMeasurements;
            }

            /** @param AlertConfigurationItem[] $rules @return \App\Waste\Domain\Model\Alert\Alert[] */
            public function check(AccountId $accountId, array $rules, \DateTimeImmutable $checkDate, ExistingAlertsIndex $existingAlerts): array
            {
                return [];
            }
        };

        $sut = new CheckAlertsService(
            alertRepository: $alertRepository,
            alertConfigurationRepository: $configRepository,
            dailyWasteWindowProvider: $dailyWasteWindowProvider,
            authorizer: new AlertAuthorizer(),
            checkers: [$stubChecker],
        );

        $result = $sut(
            new CheckAlertsRequest(
                checkDate: new \DateTimeImmutable('2026-06-01'),
                accountId: $accountId,
            ),
            UserMother::adminUser(),
        );

        $this->assertEquals(0, $result->detectedAlerts);
    }

    public function testDetectsAndPersistsAlerts(): void
    {
        $alertRepository = AlertRepositoryMother::inMemory();
        $configRepository = AlertConfigurationRepositoryMother::inMemory();
        $dailyWasteWindowProvider = new DailyWasteWindowProvider(DailyWasteRepositoryMother::inMemory());

        $accountId = new AccountId('account-id-test');
        $config = AlertConfigurationMother::from(accountId: 'account-id-test', noMeasurementsEnabled: true);
        $configRepository->add($config);

        $detectedAlert = AlertMother::detectedAlert();

        $stubChecker = new class ($detectedAlert) implements AlertChecker {
            public function __construct(private readonly \App\Waste\Domain\Model\Alert\Alert $alert) {}

            public function type(): AlertType
            {
                return AlertType::NoMeasurements;
            }

            /** @param AlertConfigurationItem[] $rules @return \App\Waste\Domain\Model\Alert\Alert[] */
            public function check(AccountId $accountId, array $rules, \DateTimeImmutable $checkDate, ExistingAlertsIndex $existingAlerts): array
            {
                return [$this->alert];
            }
        };

        $sut = new CheckAlertsService(
            alertRepository: $alertRepository,
            alertConfigurationRepository: $configRepository,
            dailyWasteWindowProvider: $dailyWasteWindowProvider,
            authorizer: new AlertAuthorizer(),
            checkers: [$stubChecker],
        );

        $result = $sut(
            new CheckAlertsRequest(
                checkDate: new \DateTimeImmutable('2026-06-01'),
                accountId: $accountId,
            ),
            UserMother::adminUser(),
        );

        $this->assertEquals(1, $result->detectedAlerts);
        $this->assertNotNull($alertRepository->find($detectedAlert->id()));
    }

    public function testAggregatesAlertsFromMultipleCheckers(): void
    {
        $alertRepository = AlertRepositoryMother::inMemory();
        $configRepository = AlertConfigurationRepositoryMother::inMemory();
        $dailyWasteWindowProvider = new DailyWasteWindowProvider(DailyWasteRepositoryMother::inMemory());

        $accountId = new AccountId('account-id-test');
        $config = AlertConfigurationMother::from(
            accountId: 'account-id-test',
            noMeasurementsEnabled: true,
            inactiveUserEnabled: true,
        );
        $configRepository->add($config);

        $noMeasurementsAlert = AlertMother::from(type: AlertType::NoMeasurements);
        $inactiveUserAlert = AlertMother::from(type: AlertType::InactiveUser);

        $noMeasurementsChecker = new class ($noMeasurementsAlert) implements AlertChecker {
            public function __construct(private readonly \App\Waste\Domain\Model\Alert\Alert $alert) {}

            public function type(): AlertType
            {
                return AlertType::NoMeasurements;
            }

            /** @param AlertConfigurationItem[] $rules @return \App\Waste\Domain\Model\Alert\Alert[] */
            public function check(AccountId $accountId, array $rules, \DateTimeImmutable $checkDate, ExistingAlertsIndex $existingAlerts): array
            {
                return [$this->alert];
            }
        };

        $inactiveUserChecker = new class ($inactiveUserAlert) implements AlertChecker {
            public function __construct(private readonly \App\Waste\Domain\Model\Alert\Alert $alert) {}

            public function type(): AlertType
            {
                return AlertType::InactiveUser;
            }

            /** @param AlertConfigurationItem[] $rules @return \App\Waste\Domain\Model\Alert\Alert[] */
            public function check(AccountId $accountId, array $rules, \DateTimeImmutable $checkDate, ExistingAlertsIndex $existingAlerts): array
            {
                return [$this->alert];
            }
        };

        $sut = new CheckAlertsService(
            alertRepository: $alertRepository,
            alertConfigurationRepository: $configRepository,
            dailyWasteWindowProvider: $dailyWasteWindowProvider,
            authorizer: new AlertAuthorizer(),
            checkers: [$noMeasurementsChecker, $inactiveUserChecker],
        );

        $result = $sut(
            new CheckAlertsRequest(
                checkDate: new \DateTimeImmutable('2026-06-01'),
                accountId: $accountId,
            ),
            UserMother::adminUser(),
        );

        $this->assertEquals(2, $result->detectedAlerts);
    }

    public function testIsHandled(): void
    {
        $sut = new CheckAlertsService(
            alertRepository: AlertRepositoryMother::inMemory(),
            alertConfigurationRepository: AlertConfigurationRepositoryMother::inMemory(),
            dailyWasteWindowProvider: new DailyWasteWindowProvider(DailyWasteRepositoryMother::inMemory()),
            authorizer: new AlertAuthorizer(),
            checkers: [],
        );

        $this->assertTrue($sut->isHandled(new CheckAlertsRequest(
            checkDate: new \DateTimeImmutable('2026-06-01'),
            accountId: new AccountId('account-id-test'),
        )));
    }
}
