<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Alert\Check;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Waste\Domain\Model\Alert\AlertMother;
use App\Tests\Unit\Waste\Domain\Model\Alert\AlertRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\AlertConfiguration\AlertConfigurationMother;
use App\Tests\Unit\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepositoryMother;
use App\Waste\Application\Alert\Check\CheckAlertsRequest;
use App\Waste\Application\Alert\Check\CheckAlertsService;
use App\Waste\Application\Alert\Check\Checker\AlertChecker;
use App\Waste\Domain\Model\Alert\Alert;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItem;
use PHPUnit\Framework\TestCase;

class CheckAlertsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testDetectsAlertsForAllAccounts(): void
    {
        $alertRepository = AlertRepositoryMother::inMemory();
        $alertConfigRepository = AlertConfigurationRepositoryMother::inMemory();
        $accountRepository = AccountRepositoryMother::inMemory();

        $account = AccountMother::fromNameAndId('Test Account', 'account-id-test');
        $accountRepository->add($account);

        $config = AlertConfigurationMother::from(accountId: 'account-id-test', noMeasurementsEnabled: true);
        $alertConfigRepository->add($config);

        $detectedAlert = AlertMother::fromIdAndAccount('alert-1', 'account-id-test');
        $fakeChecker = $this->createFakeChecker(AlertType::NoMeasurements, [$detectedAlert]);

        $sut = new CheckAlertsService(
            alertRepository: $alertRepository,
            alertConfigurationRepository: $alertConfigRepository,
            accountRepository: $accountRepository,
            checkers: [$fakeChecker],
        );

        $result = $sut(new CheckAlertsRequest(), UserMother::adminUser());

        $this->assertEquals(1, $result->detectedAlerts);
        $this->assertNotNull($alertRepository->find($detectedAlert->id()));
    }

    public function testDetectsAlertsForSpecificAccount(): void
    {
        $alertRepository = AlertRepositoryMother::inMemory();
        $alertConfigRepository = AlertConfigurationRepositoryMother::inMemory();
        $accountRepository = AccountRepositoryMother::inMemory();

        $config = AlertConfigurationMother::from(accountId: 'account-id-test', noMeasurementsEnabled: true);
        $alertConfigRepository->add($config);

        $detectedAlert = AlertMother::fromIdAndAccount('alert-1', 'account-id-test');
        $fakeChecker = $this->createFakeChecker(AlertType::NoMeasurements, [$detectedAlert]);

        $sut = new CheckAlertsService(
            alertRepository: $alertRepository,
            alertConfigurationRepository: $alertConfigRepository,
            accountRepository: $accountRepository,
            checkers: [$fakeChecker],
        );

        $result = $sut(new CheckAlertsRequest(accountId: new AccountId('account-id-test')), UserMother::adminUser());

        $this->assertEquals(1, $result->detectedAlerts);
    }

    public function testNoAlertsWhenNoEnabledConfigurations(): void
    {
        $alertRepository = AlertRepositoryMother::inMemory();
        $alertConfigRepository = AlertConfigurationRepositoryMother::inMemory();
        $accountRepository = AccountRepositoryMother::inMemory();

        $account = AccountMother::fromNameAndId('Test Account', 'account-id-test');
        $accountRepository->add($account);

        $disabledConfig = AlertConfigurationMother::from(
            accountId: 'account-id-test',
            noMeasurementsEnabled: false,
            abnormallyHighEnabled: false,
            thresholdExceededEnabled: false,
        );
        $alertConfigRepository->add($disabledConfig);

        $fakeChecker = $this->createFakeChecker(AlertType::NoMeasurements, []);

        $sut = new CheckAlertsService(
            alertRepository: $alertRepository,
            alertConfigurationRepository: $alertConfigRepository,
            accountRepository: $accountRepository,
            checkers: [$fakeChecker],
        );

        $result = $sut(new CheckAlertsRequest(), UserMother::adminUser());

        $this->assertEquals(0, $result->detectedAlerts);
    }

    public function testCheckerTypeMatchesConfigType(): void
    {
        $alertRepository = AlertRepositoryMother::inMemory();
        $alertConfigRepository = AlertConfigurationRepositoryMother::inMemory();
        $accountRepository = AccountRepositoryMother::inMemory();

        $account = AccountMother::fromNameAndId('Test Account', 'account-id-test');
        $accountRepository->add($account);

        $config = AlertConfigurationMother::from(
            accountId: 'account-id-test',
            noMeasurementsEnabled: false,
            abnormallyHighEnabled: true,
        );
        $alertConfigRepository->add($config);

        $noMeasurementsChecker = $this->createFakeChecker(AlertType::NoMeasurements, [
            AlertMother::fromIdAndAccount('alert-1', 'account-id-test'),
        ]);

        $sut = new CheckAlertsService(
            alertRepository: $alertRepository,
            alertConfigurationRepository: $alertConfigRepository,
            accountRepository: $accountRepository,
            checkers: [$noMeasurementsChecker],
        );

        $result = $sut(new CheckAlertsRequest(), UserMother::adminUser());

        $this->assertEquals(0, $result->detectedAlerts);
    }

    /** @param Alert[] $alerts */
    private function createFakeChecker(AlertType $type, array $alerts): AlertChecker
    {
        return new class ($type, $alerts) implements AlertChecker {
            /** @param Alert[] $alerts */
            public function __construct(
                private readonly AlertType $alertType,
                private readonly array $alerts,
            ) {}

            public function type(): AlertType
            {
                return $this->alertType;
            }

            /** @param AlertConfigurationItem[] $rules */
            public function check(AccountId $accountId, array $rules, \DateTimeImmutable $checkDate): array
            {
                return $this->alerts;
            }
        };
    }
}
