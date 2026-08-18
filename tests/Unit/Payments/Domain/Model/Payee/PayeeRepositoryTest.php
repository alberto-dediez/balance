<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Domain\Model\Payee;

use App\Payments\Domain\Model\Payee\PayeeFilter;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Domain\Model\Payee\PayeeStatus;
use App\Payments\Domain\Model\Payee\PayeeType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class PayeeRepositoryTest extends KernelTestCase
{
    public function testAddPayeeToRepository(): void
    {
        $repository = $this->createSUT();

        $payee = PayeeMother::ready();

        $this->assertNull($repository->find($payee->id()));

        $repository->add($payee);

        $recovered = $repository->find($payee->id());

        $this->assertNotNull($recovered);
        $this->assertTrue($payee->id()->equals($recovered->id()));
    }

    public function testFindOneBySubject(): void
    {
        $repository = $this->createSUT();

        $subjectId = (string) PayeeMother::random()->id();

        $payee = PayeeMother::ready(subjectId: $subjectId);
        $repository->add($payee);
        $repository->add(PayeeMother::random());

        $found = $repository->findOneBySubject(PayeeType::Account, $subjectId);

        $this->assertNotNull($found);
        $this->assertTrue($payee->id()->equals($found->id()));

        $this->assertNull($repository->findOneBySubject(PayeeType::Project, $subjectId));
        $this->assertNull($repository->findOneBySubject(PayeeType::Account, 'does-not-exist'));
    }

    public function testFindByFilterByType(): void
    {
        $repository = $this->createSUT();

        $repository->add(PayeeMother::random());
        $repository->add(PayeeMother::ready(type: PayeeType::Project));

        $this->assertCount(1, $repository->findByFilter(new PayeeFilter(type: PayeeType::Account, limit: 0)));
        $this->assertCount(1, $repository->findByFilter(new PayeeFilter(type: PayeeType::Project, limit: 0)));
    }

    public function testFindByFilterBySubjectId(): void
    {
        $repository = $this->createSUT();

        $subjectId = (string) PayeeMother::random()->id();

        $repository->add(PayeeMother::ready(subjectId: $subjectId));
        $repository->add(PayeeMother::random());

        $result = $repository->findByFilter(new PayeeFilter(subjectId: $subjectId, limit: 0));

        $this->assertCount(1, $result);
    }

    public function testFindByFilterByStatus(): void
    {
        $repository = $this->createSUT();

        $repository->add(PayeeMother::ready());
        $repository->add(PayeeMother::incomplete());
        $repository->add(PayeeMother::blocked());

        $this->assertCount(1, $repository->findByFilter(new PayeeFilter(status: PayeeStatus::Ready, limit: 0)));
        $this->assertCount(1, $repository->findByFilter(new PayeeFilter(status: PayeeStatus::Incomplete, limit: 0)));
        $this->assertCount(1, $repository->findByFilter(new PayeeFilter(status: PayeeStatus::Blocked, limit: 0)));
    }

    public function testFindByFilterBySearch(): void
    {
        $repository = $this->createSUT();

        $target = PayeeMother::random();
        $repository->add($target);
        $repository->add(PayeeMother::random());

        $token = substr($target->legalName(), 6);

        $this->assertCount(1, $repository->findByFilter(new PayeeFilter(search: $token, limit: 0)));
    }

    public function testTotalResultsIgnoresPagination(): void
    {
        $repository = $this->createSUT();

        $repository->add(PayeeMother::random());
        $repository->add(PayeeMother::random());
        $repository->add(PayeeMother::random());

        $filter = new PayeeFilter(limit: 2);

        $this->assertCount(2, $repository->findByFilter($filter));
        $this->assertSame(3, $repository->totalResultsByFilter($filter));
    }

    abstract protected function createSUT(): PayeeRepository;
}
