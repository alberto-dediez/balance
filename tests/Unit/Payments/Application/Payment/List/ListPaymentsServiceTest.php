<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payment\List;

use App\Payments\Application\Payment\List\ListPaymentsRequest;
use App\Payments\Application\Payment\List\ListPaymentsService;
use App\Payments\Application\Payment\PaymentAuthorizer;
use App\Payments\Domain\Model\Payment\PaymentRepository;
use App\Payments\Domain\Model\Payment\PaymentStatus;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Payments\Domain\Model\Payment\PaymentMother;
use App\Tests\Unit\Payments\Domain\Model\Payment\PaymentRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListPaymentsServiceTest extends TestCase
{
    public function testAdminListsAllPayments(): void
    {
        $payments = PaymentRepositoryMother::inMemory();
        $payments->add(PaymentMother::random());
        $payments->add(PaymentMother::random());

        $result = $this->createSUT($payments)(new ListPaymentsRequest(limit: 0), UserMother::adminUser());

        $this->assertSame(2, $result->totalResults);
        $this->assertCount(2, $result->payments);
    }

    public function testTheFilterIsApplied(): void
    {
        $payments = PaymentRepositoryMother::inMemory();
        $payments->add(PaymentMother::paid());
        $payments->add(PaymentMother::pending());

        $result = $this->createSUT($payments)(
            new ListPaymentsRequest(status: PaymentStatus::Paid, limit: 0),
            UserMother::adminUser(),
        );

        $this->assertSame(1, $result->totalResults);
    }

    public function testThrowsWhenTheUserIsNotAnAdmin(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(new ListPaymentsRequest(limit: 0), $user);
    }

    private function createSUT(?PaymentRepository $payments = null): ListPaymentsService
    {
        return new ListPaymentsService(
            $payments ?? PaymentRepositoryMother::inMemory(),
            new PaymentAuthorizer(),
        );
    }
}
