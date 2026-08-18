<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Domain\Model\Payout;

use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Domain\Model\Payout\PayoutFilter;
use App\Payments\Domain\Model\Payout\PayoutRepository;
use App\Payments\Domain\Model\Payout\PayoutStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class PayoutRepositoryTest extends KernelTestCase
{
    public function testAddPayoutToRepository(): void
    {
        $repository = $this->createSUT();

        $payout = PayoutMother::random();

        $this->assertNull($repository->find($payout->id()));

        $repository->add($payout);

        $recovered = $repository->find($payout->id());

        $this->assertNotNull($recovered);
        $this->assertTrue($payout->id()->equals($recovered->id()));
    }

    public function testFindOneByNumber(): void
    {
        $repository = $this->createSUT();

        $payout = PayoutMother::random();
        $repository->add($payout);
        $repository->add(PayoutMother::random());

        $found = $repository->findOneByNumber((string) $payout->number());

        $this->assertNotNull($found);
        $this->assertTrue($payout->id()->equals($found->id()));

        $this->assertNull($repository->findOneByNumber('does-not-exist'));
    }

    public function testFindByFilterByPayeeId(): void
    {
        $repository = $this->createSUT();

        $payeeId = PayeeId::generate();

        $repository->add(PayoutMother::draft($payeeId));
        $repository->add(PayoutMother::random());
        $repository->add(PayoutMother::random());

        $result = $repository->findByFilter(new PayoutFilter(payeeId: $payeeId, limit: 0));

        $this->assertCount(1, $result);
    }

    public function testFindByFilterByStatus(): void
    {
        $repository = $this->createSUT();

        $repository->add(PayoutMother::draft());
        $repository->add(PayoutMother::sent());
        $repository->add(PayoutMother::paid());
        $repository->add(PayoutMother::failed());
        $repository->add(PayoutMother::cancelled());

        $this->assertCount(1, $repository->findByFilter(new PayoutFilter(status: PayoutStatus::Draft, limit: 0)));
        $this->assertCount(1, $repository->findByFilter(new PayoutFilter(status: PayoutStatus::Sent, limit: 0)));
        $this->assertCount(1, $repository->findByFilter(new PayoutFilter(status: PayoutStatus::Paid, limit: 0)));
        $this->assertCount(1, $repository->findByFilter(new PayoutFilter(status: PayoutStatus::Failed, limit: 0)));
        $this->assertCount(1, $repository->findByFilter(new PayoutFilter(status: PayoutStatus::Cancelled, limit: 0)));
    }

    public function testFindByFilterByNumber(): void
    {
        $repository = $this->createSUT();

        $target = PayoutMother::random();
        $repository->add($target);
        $repository->add(PayoutMother::random());

        $result = $repository->findByFilter(new PayoutFilter(number: (string) $target->number(), limit: 0));

        $this->assertCount(1, $result);
    }

    public function testTotalResultsIgnoresPagination(): void
    {
        $repository = $this->createSUT();

        $repository->add(PayoutMother::random());
        $repository->add(PayoutMother::random());
        $repository->add(PayoutMother::random());

        $filter = new PayoutFilter(limit: 2);

        $this->assertCount(2, $repository->findByFilter($filter));
        $this->assertSame(3, $repository->totalResultsByFilter($filter));
    }

    abstract protected function createSUT(): PayoutRepository;
}
