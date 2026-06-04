<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Alert\Check\Checker;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;
use App\Waste\Application\Alert\Check\Checker\InactiveUserAlertChecker;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\Alert\ExistingAlertsIndex;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItem;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItemId;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use PHPUnit\Framework\TestCase;

class InactiveUserAlertCheckerTest extends TestCase
{
    public function testTypeReturnsInactiveUser(): void
    {
        $sut = $this->createSUT();

        $this->assertEquals(AlertType::InactiveUser, $sut->type());
    }

    public function testReturnsNoAlertsWhenAllUsersHaveRecentMeasurements(): void
    {
        $userRepository = UserRepositoryMother::inMemory();
        $measurementRepository = MeasurementRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');
        $userId = UserId::generate();

        $user = UserMother::fromUsernameAndRoleAndAccount('active-user', \App\Security\Domain\Model\User\Role::User, $accountId);
        $userRepository->add($user);

        $checkDate = new \DateTimeImmutable('2026-06-01');
        $recentMeasurement = MeasurementMother::from(
            measurementId: MeasurementId::generate(),
            propertyId: \App\Property\Domain\Model\Property\PropertyId::generate(),
            wasteId: \App\Waste\Domain\Model\Waste\WasteId::generate(),
            wasteManagerId: \App\Waste\Domain\Model\WasteManager\WasteManagerId::generate(),
            containerId: ContainerId::generate(),
            registeredBy: $user->id(),
            accountId: $accountId,
            registeredAt: $checkDate->modify('-3 days'),
        );
        $measurementRepository->add($recentMeasurement);

        $sut = new InactiveUserAlertChecker($userRepository, $measurementRepository);

        $alerts = $sut->check($accountId, [$this->createRule()], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(0, $alerts);
    }

    public function testDetectsAlertWhenUserHasNoRecentMeasurements(): void
    {
        $userRepository = UserRepositoryMother::inMemory();
        $measurementRepository = MeasurementRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');

        $user = UserMother::fromUsernameAndRoleAndAccount('inactive-user', \App\Security\Domain\Model\User\Role::User, $accountId);
        $userRepository->add($user);

        $sut = new InactiveUserAlertChecker($userRepository, $measurementRepository);

        $checkDate = new \DateTimeImmutable('2026-06-01');
        $alerts = $sut->check($accountId, [$this->createRule()], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(1, $alerts);
        $this->assertEquals(AlertType::InactiveUser, $alerts[0]->type());
        $this->assertEquals('account-id-test', (string) $alerts[0]->accountId());
    }

    public function testSkipsAlertIfAlreadyInExistingAlertsIndex(): void
    {
        $userRepository = UserRepositoryMother::inMemory();
        $measurementRepository = MeasurementRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');

        $user = UserMother::fromUsernameAndRoleAndAccount('inactive-user', \App\Security\Domain\Model\User\Role::User, $accountId);
        $userRepository->add($user);

        $sut = new InactiveUserAlertChecker($userRepository, $measurementRepository);

        $checkDate = new \DateTimeImmutable('2026-06-01');

        $existingAlerts = new ExistingAlertsIndex();
        $existingAlerts->add(AlertType::InactiveUser, null, null);

        $alerts = $sut->check($accountId, [$this->createRule()], $checkDate, $existingAlerts);

        $this->assertCount(0, $alerts);
    }

    public function testReturnsNoAlertsWhenAccountHasNoUsers(): void
    {
        $userRepository = UserRepositoryMother::inMemory();
        $measurementRepository = MeasurementRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');

        $sut = new InactiveUserAlertChecker($userRepository, $measurementRepository);

        $checkDate = new \DateTimeImmutable('2026-06-01');
        $alerts = $sut->check($accountId, [$this->createRule()], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(0, $alerts);
    }

    private function createSUT(): InactiveUserAlertChecker
    {
        return new InactiveUserAlertChecker(
            userRepository: UserRepositoryMother::inMemory(),
            measurementRepository: MeasurementRepositoryMother::inMemory(),
        );
    }

    private function createRule(): AlertConfigurationItem
    {
        return new AlertConfigurationItem(
            id: AlertConfigurationItemId::generate(),
            type: AlertType::InactiveUser,
            enabled: true,
            parameters: ['inactiveDays' => 7],
        );
    }
}
