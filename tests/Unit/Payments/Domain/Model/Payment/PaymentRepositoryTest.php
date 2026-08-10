<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Domain\Model\Payment;

use App\Payments\Domain\Model\Payment\PaymentFilter;
use App\Payments\Domain\Model\Payment\PaymentRepository;
use App\Payments\Domain\Model\Payment\PaymentStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class PaymentRepositoryTest extends KernelTestCase
{
    public function testAddPaymentToRepository(): void
    {
        $repository = $this->createSUT();

        $payment = PaymentMother::random();

        $this->assertNull($repository->find($payment->id()));

        $repository->add($payment);

        $recovered = $repository->find($payment->id());

        $this->assertNotNull($recovered);
        $this->assertTrue($payment->id()->equals($recovered->id()));
    }

    public function testFindOneByExternalId(): void
    {
        $repository = $this->createSUT();

        $payment = PaymentMother::pending();
        $repository->add($payment);
        $repository->add(PaymentMother::random());

        $externalId = $payment->externalId();

        $this->assertNotNull($externalId);

        $found = $repository->findOneByExternalId($externalId);

        $this->assertNotNull($found);
        $this->assertTrue($payment->id()->equals($found->id()));

        $this->assertNull($repository->findOneByExternalId('does-not-exist'));
    }

    public function testFindByFilterBySubject(): void
    {
        $repository = $this->createSUT();

        $subjectId = (string) PaymentMother::random()->id();

        $repository->add(PaymentMother::pending(subjectId: $subjectId));
        $repository->add(PaymentMother::random());

        $result = $repository->findByFilter(new PaymentFilter(
            subjectType: 'balance.project_contribution',
            subjectId: $subjectId,
            limit: 0,
        ));

        $this->assertCount(1, $result);
    }

    public function testFindByFilterByStatus(): void
    {
        $repository = $this->createSUT();

        $repository->add(PaymentMother::paid());
        $repository->add(PaymentMother::failed());
        $repository->add(PaymentMother::pending());

        $this->assertCount(1, $repository->findByFilter(new PaymentFilter(status: PaymentStatus::Paid, limit: 0)));
        $this->assertCount(1, $repository->findByFilter(new PaymentFilter(status: PaymentStatus::Failed, limit: 0)));
        $this->assertCount(1, $repository->findByFilter(new PaymentFilter(status: PaymentStatus::Pending, limit: 0)));
    }

    public function testFindByFilterByExternalId(): void
    {
        $repository = $this->createSUT();

        $payment = PaymentMother::pending();
        $repository->add($payment);
        $repository->add(PaymentMother::random());

        $result = $repository->findByFilter(new PaymentFilter(externalId: $payment->externalId(), limit: 0));

        $this->assertCount(1, $result);
    }

    public function testTotalResultsIgnoresPagination(): void
    {
        $repository = $this->createSUT();

        $repository->add(PaymentMother::random());
        $repository->add(PaymentMother::random());
        $repository->add(PaymentMother::random());

        $filter = new PaymentFilter(limit: 2);

        $this->assertCount(2, $repository->findByFilter($filter));
        $this->assertSame(3, $repository->totalResultsByFilter($filter));
    }

    abstract protected function createSUT(): PaymentRepository;
}
