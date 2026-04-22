<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\JobProgress;

use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Event\JobProgress\JobProgressAdvanced;
use App\Shared\Domain\Event\JobProgress\JobProgressCompleted;
use App\Shared\Domain\Event\JobProgress\JobProgressFailed;
use App\Shared\Domain\Model\JobProgress\JobProgress;
use App\Shared\Domain\Model\JobProgress\JobProgressKey;
use App\Shared\Domain\Model\JobProgress\JobProgressStatus;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class JobProgressTest extends TestCase
{
    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testStartCreatesInProgressJobWithZeroCurrent(): void
    {
        $job = JobProgress::start(JobProgressKey::fromString('test-key'), 10);

        $this->assertEquals(0, $job->current());
        $this->assertEquals(JobProgressStatus::InProgress, $job->status());
        $this->assertEquals(0.0, $job->percentage());
    }

    public function testAdvanceIncrementsCurrentAndPublishesEvent(): void
    {
        $job = JobProgress::start(JobProgressKey::fromString('test-key'), 10);

        $job->advance('step-one');

        $this->assertEquals(1, $job->current());
        $this->assertEquals(10.0, $job->percentage());

        $event = $this->spy->lastEvent();
        $this->assertInstanceOf(JobProgressAdvanced::class, $event);
        $this->assertEquals('step-one', $event->step());
        $this->assertEquals(1, $event->current());
        $this->assertEquals(10, $event->total());
    }

    public function testAdvanceMultipleTimesAccumulatesCorrectly(): void
    {
        $job = JobProgress::start(JobProgressKey::fromString('test-key'), 4);

        $job->advance('step-1');
        $job->advance('step-2');

        $this->assertEquals(2, $job->current());
        $this->assertEquals(50.0, $job->percentage());
    }

    public function testCompleteMarksStatusCompletedAndSetsCurrentToTotal(): void
    {
        $job = JobProgress::start(JobProgressKey::fromString('test-key'), 5);

        $job->complete();

        $this->assertEquals(JobProgressStatus::Completed, $job->status());
        $this->assertTrue($job->status()->isCompleted());
        $this->assertEquals(5, $job->current());
        $this->assertInstanceOf(JobProgressCompleted::class, $this->spy->lastEvent());
    }

    public function testFailMarksStatusFailed(): void
    {
        $job = JobProgress::start(JobProgressKey::fromString('test-key'), 5);

        $job->fail();

        $this->assertEquals(JobProgressStatus::Failed, $job->status());
        $this->assertTrue($job->status()->isFailed());
        $this->assertInstanceOf(JobProgressFailed::class, $this->spy->lastEvent());
    }

    public function testResizeChangesTotalSteps(): void
    {
        $job = JobProgress::start(JobProgressKey::fromString('test-key'), 5);

        $job->resize(20);
        $job->advance('step');

        $this->assertEquals(5.0, $job->percentage());
    }

    public function testPercentageReturnsZeroWhenTotalIsZero(): void
    {
        $job = JobProgress::start(JobProgressKey::fromString('test-key'), 0);

        $this->assertEquals(0.0, $job->percentage());
    }

    public function testResetRestoresInitialState(): void
    {
        $job = JobProgress::start(JobProgressKey::fromString('test-key'), 5);
        $job->advance('step');

        $job->reset();

        $this->assertEquals(0, $job->current());
        $this->assertEquals('', $job->currentStep());
        $this->assertEquals(JobProgressStatus::InProgress, $job->status());
    }

    public function testKeyIsRetained(): void
    {
        $key = JobProgressKey::fromString('my-unique-key');
        $job = JobProgress::start($key, 3);

        $this->assertEquals('my-unique-key', (string) $job->key());
    }
}
