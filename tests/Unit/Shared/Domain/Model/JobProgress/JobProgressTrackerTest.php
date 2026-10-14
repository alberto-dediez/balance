<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\JobProgress;

use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Event\JobProgress\JobProgressAdvanced;
use App\Shared\Domain\Model\JobProgress\JobProgress;
use App\Shared\Domain\Model\JobProgress\JobProgressKey;
use App\Shared\Domain\Model\JobProgress\JobProgressTracker;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class JobProgressTrackerTest extends TestCase
{
    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();

        JobProgressTracker::instance()->clear();
    }

    public function testInstanceReturnsSameSingleton(): void
    {
        $a = JobProgressTracker::instance();
        $b = JobProgressTracker::instance();

        $this->assertSame($a, $b);
    }

    public function testCloneThrowsBadMethodCallException(): void
    {
        $this->expectException(\BadMethodCallException::class);

        clone JobProgressTracker::instance();
    }

    public function testAdvanceForwardsToCurrentJobProgress(): void
    {
        $job = JobProgress::start(JobProgressKey::fromString('tracker-test'), 5);

        $tracker = JobProgressTracker::instance();
        $tracker->set($job);
        $tracker->advance('step-one');

        $this->assertEquals(1, $job->current());
        $this->assertInstanceOf(JobProgressAdvanced::class, $this->spy->lastEvent());
    }

    public function testAdvanceDoesNothingWhenNoCurrent(): void
    {
        $tracker = JobProgressTracker::instance();

        $tracker->advance('step-one');

        $this->assertNull($this->spy->lastEvent());
    }

    public function testResizeForwardsToCurrentJobProgress(): void
    {
        $job = JobProgress::start(JobProgressKey::fromString('tracker-resize'), 5);

        $tracker = JobProgressTracker::instance();
        $tracker->set($job);
        $tracker->resize(20);
        $tracker->advance('x');

        $this->assertEquals(5.0, $job->percentage());
    }

    public function testResizeDoesNothingWhenNoCurrent(): void
    {
        $tracker = JobProgressTracker::instance();

        $tracker->resize(100);

        $this->assertNull($this->spy->lastEvent());
    }

    public function testClearDetachesCurrentJobProgress(): void
    {
        $job = JobProgress::start(JobProgressKey::fromString('tracker-clear'), 3);

        $tracker = JobProgressTracker::instance();
        $tracker->set($job);
        $tracker->clear();
        $tracker->advance('step');

        $this->assertEquals(0, $job->current());
        $this->assertNull($this->spy->lastEvent());
    }

    public function testSetReplacesExistingJobProgress(): void
    {
        $jobA = JobProgress::start(JobProgressKey::fromString('job-a'), 3);
        $jobB = JobProgress::start(JobProgressKey::fromString('job-b'), 10);

        $tracker = JobProgressTracker::instance();
        $tracker->set($jobA);
        $tracker->set($jobB);
        $tracker->advance('step');

        $this->assertEquals(0, $jobA->current());
        $this->assertEquals(1, $jobB->current());
    }
}
