<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\TrainingAttendance\CancelSignatureProcess;

use App\FoodLoss\Application\TrainingAttendance\CancelSignatureProcess\CancelTrainingAttendanceSignatureProcessRequest;
use App\FoodLoss\Application\TrainingAttendance\CancelSignatureProcess\CancelTrainingAttendanceSignatureProcessService;
use App\FoodLoss\Application\TrainingAttendance\TrainingAttendanceAuthorizer;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\HumanResources\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepositoryMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CancelTrainingAttendanceSignatureProcessServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testCancelsSignatureProcess(): void
    {
        $repository = TrainingAttendanceRepositoryMother::inMemory();
        $signatureRequestId = SignatureRequestId::generate();

        $attendance = TrainingAttendanceMother::inSignatureProcess(
            accountId: 'account-test',
            signatureRequestId: $signatureRequestId,
        );
        $repository->add($attendance);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));
        $sut = $this->createSUT($repository);

        $result = $sut(new CancelTrainingAttendanceSignatureProcessRequest($signatureRequestId), $user);

        $this->assertEquals(1, $result->cancelledCount);
        $this->assertFalse($attendance->isInSignatureProcess());
        $this->assertNull($attendance->signatureRequestId());
    }

    public function testCancelsMultipleAttendancesForSameSignatureRequest(): void
    {
        $repository = TrainingAttendanceRepositoryMother::inMemory();
        $signatureRequestId = SignatureRequestId::generate();

        $attendance1 = TrainingAttendanceMother::inSignatureProcess(
            accountId: 'account-test',
            signatureRequestId: $signatureRequestId,
        );
        $attendance2 = TrainingAttendanceMother::inSignatureProcess(
            accountId: 'account-test',
            signatureRequestId: $signatureRequestId,
        );
        $repository->add($attendance1);
        $repository->add($attendance2);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));
        $sut = $this->createSUT($repository);

        $result = $sut(new CancelTrainingAttendanceSignatureProcessRequest($signatureRequestId), $user);

        $this->assertEquals(2, $result->cancelledCount);
        $this->assertFalse($attendance1->isInSignatureProcess());
        $this->assertFalse($attendance2->isInSignatureProcess());
    }

    public function testReturnsZeroWhenNoAttendanceMatchesSignatureRequest(): void
    {
        $repository = TrainingAttendanceRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));
        $sut = $this->createSUT($repository);

        $result = $sut(new CancelTrainingAttendanceSignatureProcessRequest(SignatureRequestId::generate()), $user);

        $this->assertEquals(0, $result->cancelledCount);
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(TrainingAttendanceRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled(new CancelTrainingAttendanceSignatureProcessRequest(SignatureRequestId::generate())));
    }

    private function createSUT(TrainingAttendanceRepository $repository): CancelTrainingAttendanceSignatureProcessService
    {
        return new CancelTrainingAttendanceSignatureProcessService(
            trainingAttendanceRepository: $repository,
            authorizer: new TrainingAttendanceAuthorizer(
                EmployeeRepositoryMother::inMemory()
            ),
        );
    }
}
