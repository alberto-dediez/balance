<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessment;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentFilter;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRepository;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentStatus;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class MaterialityAssessmentRepositoryTest extends KernelTestCase
{
    public function testAddAndFind(): void
    {
        $repository = $this->createSUT();

        $materialityAssessment = MaterialityAssessmentMother::draft();

        $this->assertNull($repository->find($materialityAssessment->id()));

        $repository->add($materialityAssessment);

        $recovered = $repository->find($materialityAssessment->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($materialityAssessment->id(), $recovered->id());
    }

    public function testRemove(): void
    {
        $repository = $this->createSUT();

        $materialityAssessment = MaterialityAssessmentMother::draft();

        $repository->add($materialityAssessment);
        $repository->remove($materialityAssessment);

        $this->assertNull($repository->find($materialityAssessment->id()));
    }

    public function testFindDoesNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $materialityAssessment = MaterialityAssessmentMother::draft();
        $repository->add($materialityAssessment);

        $materialityAssessment->remove();
        $repository->add($materialityAssessment);

        $this->assertNull($repository->find($materialityAssessment->id()));
        $this->assertSame(0, $repository->totalResultsByFilter(new MaterialityAssessmentFilter()));
        $this->assertSame(1, $repository->totalResultsByFilter(new MaterialityAssessmentFilter(includeRemoved: true)));
    }

    public function testFindCurrentByCarbonFootprint(): void
    {
        $repository = $this->createSUT();

        $removed = $this->assessment('footprint-1', registeredAt: '2026-01-03');
        $removed->remove();
        $repository->add($removed);
        $older = $this->assessment('footprint-1', registeredAt: '2026-01-01');
        $repository->add($older);
        $newer = $this->assessment('footprint-1', registeredAt: '2026-01-02');
        $repository->add($newer);
        $repository->add($this->assessment('footprint-1', MaterialityAssessmentSubjectType::Company, registeredAt: '2026-01-04'));
        $repository->add($this->assessment('footprint-2', registeredAt: '2026-01-05'));

        $recovered = $repository->findCurrentByCarbonFootprint(MaterialityAssessmentSubjectType::Property, 'footprint-1');

        $this->assertNotNull($recovered);
        $this->assertEquals($newer->id(), $recovered->id());
        $this->assertNull($repository->findCurrentByCarbonFootprint(MaterialityAssessmentSubjectType::Property, 'footprint-3'));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(MaterialityAssessmentMother::draft(carbonFootprintId: 'footprint-1', accountId: 'account-1', year: 2024));
        $repository->add(MaterialityAssessmentMother::completed(subjectType: MaterialityAssessmentSubjectType::Company, carbonFootprintId: 'footprint-2', accountId: 'account-1', year: 2025));
        $repository->add(MaterialityAssessmentMother::draft(carbonFootprintId: 'footprint-3', accountId: 'account-2', year: 2025));

        $this->assertCount(2, $repository->findByFilter(new MaterialityAssessmentFilter(subjectType: MaterialityAssessmentSubjectType::Property)));
        $this->assertCount(1, $repository->findByFilter(new MaterialityAssessmentFilter(carbonFootprintId: 'footprint-2')));
        $this->assertCount(2, $repository->findByFilter(new MaterialityAssessmentFilter(accountId: new AccountId('account-1'))));
        $this->assertCount(2, $repository->findByFilter(new MaterialityAssessmentFilter(year: 2025)));
        $this->assertCount(1, $repository->findByFilter(new MaterialityAssessmentFilter(status: MaterialityAssessmentStatus::Completed)));
        $this->assertCount(2, $repository->findByFilter(new MaterialityAssessmentFilter(limit: 2)));
        $this->assertSame(3, $repository->totalResultsByFilter(new MaterialityAssessmentFilter(limit: 2)));
        $this->assertSame(2, $repository->totalResultsByFilter(new MaterialityAssessmentFilter(status: MaterialityAssessmentStatus::Draft)));
    }

    public function testChildrenAreRecoveredWithTheirValues(): void
    {
        $repository = $this->createSUT();

        $materialityAssessment = MaterialityAssessmentMother::completed(value: 2);
        $repository->add($materialityAssessment);

        $recovered = $this->recover($repository, $materialityAssessment->id());

        $this->assertNotNull($recovered);
        $this->assertTrue($recovered->isCompleted());
        $this->assertCount(1, $recovered->topics());
        $this->assertSame(50.0, $recovered->topics()[0]->scorePercent());
        $this->assertCount(1, $recovered->answers());
        $this->assertSame(2, $recovered->answers()[0]->value());
    }

    protected function recover(MaterialityAssessmentRepository $repository, MaterialityAssessmentId $id): ?MaterialityAssessment
    {
        return $repository->find($id);
    }

    private function assessment(
        string $carbonFootprintId,
        MaterialityAssessmentSubjectType $subjectType = MaterialityAssessmentSubjectType::Property,
        string $registeredAt = 'NOW',
    ): MaterialityAssessment {
        $materialityAssessment = new MaterialityAssessment(
            id: MaterialityAssessmentId::generate(),
            subjectType: $subjectType,
            carbonFootprintId: $carbonFootprintId,
            accountId: new AccountId('account-id'),
            year: 2025,
            registeredAt: new \DateTimeImmutable($registeredAt),
        );

        return $materialityAssessment;
    }

    abstract protected function createSUT(): MaterialityAssessmentRepository;
}
