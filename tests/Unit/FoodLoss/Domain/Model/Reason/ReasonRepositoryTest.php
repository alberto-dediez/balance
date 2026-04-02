<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Reason;

use App\FoodLoss\Domain\Model\Reason\ReasonFilter;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ReasonRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddReasonToRepository(): void
    {
        $repository = $this->createSUT();

        $reason = ReasonMother::registeredReason();

        $this->assertNull($repository->find($reason->id()));

        $repository->add($reason);

        $recoverReason = $repository->find($reason->id());

        $this->assertNotNull($recoverReason);
        $this->assertEquals($reason->id(), $recoverReason->id());
    }

    public function testRemoveReasonToRepository(): void
    {
        $repository = $this->createSUT();

        $reason = ReasonMother::registeredReason();

        $repository->add($reason);

        $recoverReason = $repository->find($reason->id());

        $this->assertNotNull($recoverReason);
        $this->assertEquals($reason->id(), $recoverReason->id());

        $repository->remove($reason);

        $this->assertNull($repository->find($reason->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(ReasonMother::random());
        $repository->add(ReasonMother::random());
        $repository->add(ReasonMother::random());
        $repository->add(ReasonMother::random());
        $repository->add(ReasonMother::random());
        $repository->add(ReasonMother::fromDescription('Descripción específica'));

        $filter = new ReasonFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new ReasonFilter(
            description: 'Descripción específica',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(ReasonMother::random());
        $repository->add(ReasonMother::random());
        $repository->add(ReasonMother::random());
        $repository->add(ReasonMother::random());
        $repository->add(ReasonMother::random());
        $repository->add(ReasonMother::fromDescription('Descripción específica'));

        $filter = new ReasonFilter(
            limit: 0,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $reason = ReasonMother::registeredReason();

        $repository->add($reason);

        $recoverReason = $repository->find($reason->id());

        $this->assertNotNull($recoverReason);

        $recoverReason->remove();

        $repository->add($reason);

        $this->assertNull($repository->find($reason->id()));
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): ReasonRepository;
}
