<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Process;

use App\CarbonFootprint\Domain\Model\Process\Process;
use App\CarbonFootprint\Domain\Model\Process\ProcessFilter;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ProcessRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddProcessToRepository(): void
    {
        $repository = $this->createSUT();

        $process = ProcessMother::registeredProcess();

        $this->assertNull($repository->find($process->id()));

        $repository->add($process);

        $recoveredProcess = $repository->find($process->id());

        $this->assertNotNull($recoveredProcess);
        $this->assertEquals($process->id(), $recoveredProcess->id());
    }

    public function testRemoveProcessToRepository(): void
    {
        $repository = $this->createSUT();

        $process = ProcessMother::registeredProcess();

        $repository->add($process);

        $recoveredProcess = $repository->find($process->id());

        $this->assertNotNull($recoveredProcess);
        $this->assertEquals($process->id(), $recoveredProcess->id());

        $repository->remove($process);

        $this->assertNull($repository->find($process->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(ProcessMother::random());
        $repository->add(ProcessMother::random());
        $repository->add(ProcessMother::random());
        $repository->add(ProcessMother::random());
        $repository->add(ProcessMother::random());
        $repository->add(ProcessMother::fromName('Nombre específico'));

        $filter = new ProcessFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new ProcessFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(ProcessMother::random());
        $repository->add(ProcessMother::random());
        $repository->add(ProcessMother::random());
        $repository->add(ProcessMother::random());
        $repository->add(ProcessMother::random());
        $repository->add(ProcessMother::fromName('Nombre específico'));

        $filter = new ProcessFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $process = ProcessMother::registeredProcess();

        $repository->add($process);

        $recoveredProcess = $repository->find($process->id());

        $this->assertNotNull($recoveredProcess);

        $recoveredProcess->remove();

        $repository->add($process);

        $this->assertNull($repository->find($process->id()));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $process = ProcessMother::registeredProcess();

        $this->assertNull($repository->find($process->id()));

        $process->translate('translated-name', Locale::English);

        $repository->add($process);

        $recoveredProcess = $repository->find($process->id());

        $this->assertNotNull($recoveredProcess);
        $this->assertEquals($process->id(), $recoveredProcess->id());
        $this->assertEquals('translated-name', $recoveredProcess->name());
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $process = ProcessMother::registeredProcess();

        $process->translate('translated-name', Locale::English);

        $repository->add($process);

        $recoveredProcesses = $repository->findByFilter(new ProcessFilter(name: 'translated-name'));

        $this->assertCount(1, $recoveredProcesses);
        $this->assertEquals($process->id(), $recoveredProcesses[0]->id());
        $this->assertEquals('translated-name', $recoveredProcesses[0]->name());
    }

    public function testFindByFilterOrdersByName(): void
    {
        $repository = $this->createSUT();

        $repository->add(ProcessMother::fromName('B'));
        $repository->add(ProcessMother::fromName('C'));
        $repository->add(ProcessMother::fromName('A'));

        $filter = new ProcessFilter();

        $this->assertSame(['A', 'B', 'C'], $this->names($repository->findByFilter($filter)));

        $filter->orderWay = 'DESC';

        $this->assertSame(['C', 'B', 'A'], $this->names($repository->findByFilter($filter)));
    }

    public function testFindByFilterOrdersByRegisteredAt(): void
    {
        $repository = $this->createSUT();

        $repository->add($this->process('A', registeredAt: new \DateTimeImmutable('2026-03-01')));
        $repository->add($this->process('B', registeredAt: new \DateTimeImmutable('2026-01-01')));
        $repository->add($this->process('C', registeredAt: new \DateTimeImmutable('2026-02-01')));

        $filter = new ProcessFilter();
        $filter->orderBy = 'registeredAt';

        $this->assertSame(['B', 'C', 'A'], $this->names($repository->findByFilter($filter)));
    }

    public function testFindByFilterOrdersByUpdatedAt(): void
    {
        $repository = $this->createSUT();

        $repository->add($this->process('A', updatedAt: new \DateTimeImmutable('2026-03-01')));
        $repository->add($this->process('B', updatedAt: new \DateTimeImmutable('2026-01-01')));
        $repository->add($this->process('C', updatedAt: new \DateTimeImmutable('2026-02-01')));

        $filter = new ProcessFilter();
        $filter->orderBy = 'updatedAt';
        $filter->orderWay = 'DESC';

        $this->assertSame(['A', 'C', 'B'], $this->names($repository->findByFilter($filter)));
    }

    public function testFindByFilterPaginatesAndCountsTotal(): void
    {
        $repository = $this->createSUT();

        foreach (['A', 'B', 'C', 'D', 'E'] as $name) {
            $repository->add(ProcessMother::fromName($name));
        }

        $filter = new ProcessFilter(limit: 2);
        $filter->page = 1;

        $this->assertSame(['C', 'D'], $this->names($repository->findByFilter($filter)));
        $this->assertSame(5, $repository->totalResultsByFilter($filter));
    }

    private function process(
        string $name,
        \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        ?\DateTimeImmutable $updatedAt = null,
    ): Process {
        return new Process(
            id: ProcessId::generate(),
            name: $name,
            unit: Unit::Piece,
            registeredAt: $registeredAt,
            updatedAt: $updatedAt,
        );
    }

    /**
     * @param array<Process> $processes
     *
     * @return list<string>
     */
    private function names(array $processes): array
    {
        return array_values(array_map(static fn(Process $process): string => $process->name(), $processes));
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): ProcessRepository;
}
