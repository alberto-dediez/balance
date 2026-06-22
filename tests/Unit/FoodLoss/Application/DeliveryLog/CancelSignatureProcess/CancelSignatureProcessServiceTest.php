<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\DeliveryLog\CancelSignatureProcess;

use App\FoodLoss\Application\DeliveryLog\CancelSignatureProcess\CancelSignatureProcessRequest;
use App\FoodLoss\Application\DeliveryLog\CancelSignatureProcess\CancelSignatureProcessService;
use App\FoodLoss\Application\DeliveryLog\DeliveryLogAuthorizer;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Domain\Model\DeliveryLog\FoodType;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CancelSignatureProcessServiceTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, new AccountId('account-id-test'));
    }

    public function testCancelsAllLogsForGivenSignatureRequestId(): void
    {
        $repository = DeliveryLogRepositoryMother::inMemory();
        $signatureRequestId = SignatureRequestId::generate();

        $log1 = DeliveryLogMother::from(
            productType: FoodType::Fruit,
            id: 'log-1',
            accountId: 'account-id-test',
        );
        $log1->startSignatureProcess($signatureRequestId, new \DateTimeImmutable());

        $log2 = DeliveryLogMother::from(
            productType: FoodType::Vegetables,
            id: 'log-2',
            accountId: 'account-id-test',
        );
        $log2->startSignatureProcess($signatureRequestId, new \DateTimeImmutable());

        $repository->add($log1);
        $repository->add($log2);

        $result = $this->createSUT($repository)(new CancelSignatureProcessRequest($signatureRequestId), $this->user);

        $this->assertEquals(2, $result->cancelledCount);
        $this->assertNull($log1->signatureRequestId());
        $this->assertNull($log2->signatureRequestId());
        $this->assertFalse($log1->isInSignatureProcess());
        $this->assertFalse($log2->isInSignatureProcess());
    }

    public function testThrowsWhenLogIsAlreadyDeliveredViaSignature(): void
    {
        $repository = DeliveryLogRepositoryMother::inMemory();
        $signatureRequestId = SignatureRequestId::generate();

        $log = DeliveryLogMother::from(
            productType: FoodType::Fruit,
            id: 'log-1',
            accountId: 'account-id-test',
        );
        $log->startSignatureProcess($signatureRequestId, new \DateTimeImmutable());
        $log->endSignatureProcess();

        $repository->add($log);

        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT($repository)(new CancelSignatureProcessRequest($signatureRequestId), $this->user);
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(DeliveryLogRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled(new CancelSignatureProcessRequest(SignatureRequestId::generate())));
    }

    private function createSUT(DeliveryLogRepository $repository): CancelSignatureProcessService
    {
        return new CancelSignatureProcessService(
            repository: $repository,
            authorizer: new DeliveryLogAuthorizer(),
        );
    }
}
