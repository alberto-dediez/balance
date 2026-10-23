<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\SyncStorage;

use App\Library\Application\Media\MediaStorageFileCopier;
use App\Library\Application\Media\SyncStorage\SyncMediaStorageRequest;
use App\Library\Application\Media\SyncStorage\SyncMediaStorageService;
use App\Library\Domain\Model\StorageSync\MediaStorageSynchronized;
use App\Library\Domain\Model\StorageSync\StorageSyncTask;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryStorageSyncTaskRepository;
use App\Library\Infrastructure\Storage\InMemoryMediaStorage;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use App\Tests\Unit\Library\Domain\Model\StorageSync\StorageSyncTaskMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class SyncMediaStorageServiceTest extends TestCase
{
    private InMemoryMediaStorage $primary;

    private InMemoryMediaStorage $fallback;

    private InMemoryStorageSyncTaskRepository $tasks;

    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->primary = MediaStorageMother::inMemory();
        $this->fallback = MediaStorageMother::inMemory();
        $this->tasks = new InMemoryStorageSyncTaskRepository();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $this->addPending('shared/a.png', '2026-10-14 09:00:00');
        $this->addPending('shared/b.png', '2026-10-14 09:05:00');

        $response = $this->createSUT()(new SyncMediaStorageRequest(new \DateTimeImmutable('NOW')), UserMother::systemUser());

        $this->assertSame(2, $response->uploaded);
        $this->assertSame(0, $response->dropped);
        $this->assertSame(0, $response->failed);
        $this->assertSame(0, $response->pending);
        $this->assertNull($response->lastError);
        $this->assertTrue($this->primary->exists('shared/a.png'));
        $this->assertTrue($this->primary->exists('shared/b.png'));
        $this->assertFalse($this->fallback->exists('shared/a.png'));
        $this->assertFalse($this->fallback->exists('shared/b.png'));
        $this->assertSame(0, $this->tasks->countPending());
        $this->assertCount(1, $this->spySubscriber->events());

        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(MediaStorageSynchronized::class, $event);
        $this->assertSame(2, $event->uploaded());
    }

    public function testTaskWithoutFileInTheFallbackIsDropped(): void
    {
        $this->tasks->save(StorageSyncTaskMother::forPath('shared/a.png'));

        $response = $this->createSUT()(new SyncMediaStorageRequest(new \DateTimeImmutable('NOW')), UserMother::systemUser());

        $this->assertSame(0, $response->uploaded);
        $this->assertSame(1, $response->dropped);
        $this->assertSame(0, $response->failed);
        $this->assertSame(0, $response->pending);
        $this->assertSame([], $this->spySubscriber->events());
    }

    public function testStopsAtTheFirstFailure(): void
    {
        $this->addPending('shared/a.png', '2026-10-14 09:00:00');
        $this->addPending('shared/b.png', '2026-10-14 09:05:00');
        $this->addPending('shared/c.png', '2026-10-14 09:10:00');
        $this->primary->markUnavailable();

        $now = new \DateTimeImmutable('2026-10-14 10:00:00');

        $response = $this->createSUT()(new SyncMediaStorageRequest($now), UserMother::systemUser());

        $this->assertSame(0, $response->uploaded);
        $this->assertSame(1, $response->failed);
        $this->assertSame(3, $response->pending);
        $this->assertSame('Media storage unavailable for "shared/a.png"', $response->lastError);

        $first = $this->tasks->find('shared/a.png');

        $this->assertInstanceOf(StorageSyncTask::class, $first);
        $this->assertSame(1, $first->attempts());
        $this->assertSame($now, $first->lastAttemptAt());
        $this->assertSame('Media storage unavailable for "shared/a.png"', $first->lastError());
        $this->assertSame(0, $this->tasks->find('shared/b.png')?->attempts());
        $this->assertSame(0, $this->tasks->find('shared/c.png')?->attempts());
        $this->assertTrue($this->fallback->exists('shared/a.png'));
        $this->assertSame([], $this->spySubscriber->events());
    }

    public function testEmptyQueue(): void
    {
        $response = $this->createSUT()(new SyncMediaStorageRequest(new \DateTimeImmutable('NOW')), UserMother::systemUser());

        $this->assertSame(0, $response->uploaded);
        $this->assertSame(0, $response->dropped);
        $this->assertSame(0, $response->failed);
        $this->assertSame(0, $response->pending);
        $this->assertSame([], $this->spySubscriber->events());
    }

    public function testBatchSize(): void
    {
        $this->addPending('shared/a.png', '2026-10-14 09:00:00');
        $this->addPending('shared/b.png', '2026-10-14 09:05:00');

        $response = $this->createSUT()(
            new SyncMediaStorageRequest(new \DateTimeImmutable('NOW'), batchSize: 1),
            UserMother::systemUser(),
        );

        $this->assertSame(1, $response->uploaded);
        $this->assertSame(1, $response->pending);
        $this->assertTrue($this->primary->exists('shared/a.png'));
        $this->assertFalse($this->primary->exists('shared/b.png'));
        $this->assertSame([], $this->spySubscriber->events());
    }

    public function testSameStorageAsPrimaryAndFallback(): void
    {
        $this->addPending('shared/a.png', '2026-10-14 09:00:00');

        $sut = new SyncMediaStorageService($this->tasks, $this->fallback, $this->fallback, new MediaStorageFileCopier());

        $response = $sut(new SyncMediaStorageRequest(new \DateTimeImmutable('NOW')), UserMother::systemUser());

        $this->assertSame(0, $response->uploaded);
        $this->assertSame(1, $response->pending);
        $this->assertNotNull($this->tasks->find('shared/a.png'));
        $this->assertSame([], $this->spySubscriber->events());
    }

    public function testIsHandle(): void
    {
        $this->assertTrue($this->createSUT()->isHandled(new SyncMediaStorageRequest(new \DateTimeImmutable('NOW'))));
    }

    private function createSUT(): SyncMediaStorageService
    {
        return new SyncMediaStorageService($this->tasks, $this->primary, $this->fallback, new MediaStorageFileCopier());
    }

    private function addPending(string $path, string $registeredAt): void
    {
        $this->fallback->write($path, MediaStorageMother::localFile(sprintf('contents of %s', $path)));
        $this->tasks->save(StorageSyncTaskMother::forPathRegisteredAt($path, new \DateTimeImmutable($registeredAt)));
    }
}
