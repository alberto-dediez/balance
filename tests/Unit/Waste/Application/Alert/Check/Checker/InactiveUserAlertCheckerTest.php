<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Alert\Check\Checker;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\Alert\AlertRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\AlertConfiguration\AlertConfigurationMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;
use App\Waste\Application\Alert\Check\Checker\InactiveUserAlertChecker;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use PHPUnit\Framework\TestCase;

class InactiveUserAlertCheckerTest extends TestCase
{
    public function testEmitsAlertWhenUserHasNoMeasurementsInPeriod(): void
    {
        $accountId = new AccountId('account-test');

        $userRepository = UserRepositoryMother::inMemory();
        $user = UserMother::from(userId: UserId::generate(), accountId: $accountId);
        $userRepository->add($user);

        $measurementRepository = MeasurementRepositoryMother::inMemory();
        $alertRepository = AlertRepositoryMother::inMemory();

        $config = AlertConfigurationMother::from(
            accountId: (string) $accountId,
            inactiveUserEnabled: true,
            inactiveDays: 7,
        );

        $checker = new InactiveUserAlertChecker($userRepository, $measurementRepository, $alertRepository);
        $alerts = $checker->check($accountId, $config->enabledItemsForType($checker->type()), new \DateTimeImmutable('today'));

        $this->assertCount(1, $alerts);
        $this->assertNull($alerts[0]->propertyId());
        $this->assertNull($alerts[0]->wasteId());
    }

    public function testNoAlertWhenUserHasRecentMeasurement(): void
    {
        $accountId = new AccountId('account-test');
        $userId = UserId::generate();

        $userRepository = UserRepositoryMother::inMemory();
        $user = UserMother::from(userId: $userId, accountId: $accountId);
        $userRepository->add($user);

        $measurementRepository = MeasurementRepositoryMother::inMemory();
        $measurement = MeasurementMother::from(
            measurementId: MeasurementId::generate(),
            propertyId: PropertyId::generate(),
            wasteId: WasteId::generate(),
            wasteManagerId: WasteManagerId::generate(),
            containerId: ContainerId::generate(),
            registeredBy: $userId,
            accountId: $accountId,
            registeredAt: new \DateTimeImmutable('yesterday'),
        );
        $measurementRepository->add($measurement);

        $alertRepository = AlertRepositoryMother::inMemory();

        $config = AlertConfigurationMother::from(
            accountId: (string) $accountId,
            inactiveUserEnabled: true,
            inactiveDays: 7,
        );

        $checker = new InactiveUserAlertChecker($userRepository, $measurementRepository, $alertRepository);
        $alerts = $checker->check($accountId, $config->enabledItemsForType($checker->type()), new \DateTimeImmutable('today'));

        $this->assertCount(0, $alerts);
    }

    public function testNoDuplicateAlertForSameAccount(): void
    {
        $accountId = new AccountId('account-test');

        $userRepository = UserRepositoryMother::inMemory();
        $user1 = UserMother::from(userId: UserId::generate(), username: 'user1', accountId: $accountId);
        $user2 = UserMother::from(userId: UserId::generate(), username: 'user2', accountId: $accountId);
        $userRepository->add($user1);
        $userRepository->add($user2);

        $measurementRepository = MeasurementRepositoryMother::inMemory();
        $alertRepository = AlertRepositoryMother::inMemory();

        $config = AlertConfigurationMother::from(
            accountId: (string) $accountId,
            inactiveUserEnabled: true,
            inactiveDays: 7,
        );

        $checker = new InactiveUserAlertChecker($userRepository, $measurementRepository, $alertRepository);

        $firstRun = $checker->check($accountId, $config->enabledItemsForType($checker->type()), new \DateTimeImmutable('today'));
        foreach ($firstRun as $alert) {
            $alertRepository->add($alert);
        }

        $secondRun = $checker->check($accountId, $config->enabledItemsForType($checker->type()), new \DateTimeImmutable('today'));

        $this->assertCount(1, $firstRun);
        $this->assertCount(0, $secondRun);
    }
}
