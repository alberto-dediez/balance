<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Storage;

use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\NotStoredMediaException;
use App\Library\Domain\Model\StorageSync\MediaStorageFallbackActivated;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryStorageSyncTaskRepository;
use App\Library\Infrastructure\Storage\FallbackMediaStorage;
use App\Library\Infrastructure\Storage\InMemoryMediaStorage;
use App\Library\Infrastructure\Storage\LocalMediaStorage;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use App\Tests\Unit\Library\Domain\Model\StorageSync\StorageSyncTaskMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class FallbackMediaStorageTest extends TestCase
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

    public function testStoreWithThePrimaryAvailable(): void
    {
        $path = $this->createSUT()->store(MediaId::generate(), null, 'png', MediaStorageMother::localFile('logo'));

        $this->assertTrue($this->primary->exists($path));
        $this->assertFalse($this->fallback->exists($path));
        $this->assertSame(0, $this->tasks->countPending());
        $this->assertSame([], $this->spySubscriber->events());
    }

    public function testStoreWithThePrimaryUnavailable(): void
    {
        $this->primary->markUnavailable();

        $path = $this->createSUT()->store(MediaId::generate(), null, 'png', MediaStorageMother::localFile('logo'));

        $this->assertTrue($this->fallback->exists($path));
        $this->assertNotNull($this->tasks->find($path));
        $this->assertCount(1, $this->spySubscriber->events());

        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(MediaStorageFallbackActivated::class, $event);
        $this->assertSame($path, $event->path());
        $this->assertSame(sprintf('Media storage unavailable for "%s"', $path), $event->error());
    }

    public function testSecondStoreDuringTheSameOutageDoesNotWarnAgain(): void
    {
        $this->primary->markUnavailable();

        $sut = $this->createSUT();
        $sut->store(MediaId::generate(), null, 'png', MediaStorageMother::localFile('logo'));
        $sut->store(MediaId::generate(), null, 'png', MediaStorageMother::localFile('other'));

        $this->assertSame(2, $this->tasks->countPending());
        $this->assertCount(1, $this->spySubscriber->events());
    }

    public function testStoreWithBothStoragesUnavailable(): void
    {
        $this->primary->markUnavailable();
        $this->fallback->markUnavailable();

        try {
            $this->createSUT()->store(MediaId::generate(), null, 'png', MediaStorageMother::localFile('logo'));

            $this->fail('A NotStoredMediaException was expected');
        } catch (NotStoredMediaException) {
        }

        $this->assertSame(0, $this->tasks->countPending());
        $this->assertSame([], $this->spySubscriber->events());
    }

    public function testWriteToThePrimaryForgetsThePendingTask(): void
    {
        $this->tasks->save(StorageSyncTaskMother::forPath('shared/file.png'));

        $this->createSUT()->write('shared/file.png', MediaStorageMother::localFile('logo'));

        $this->assertTrue($this->primary->exists('shared/file.png'));
        $this->assertNull($this->tasks->find('shared/file.png'));
    }

    public function testReadFromTheFallbackWithThePrimaryUnavailable(): void
    {
        $this->fallback->write('shared/file.txt', MediaStorageMother::localFile('hola'));
        $this->primary->markUnavailable();

        $stream = $this->createSUT()->read('shared/file.txt');

        $this->assertSame('hola', stream_get_contents($stream));

        fclose($stream);
    }

    public function testReadAMissingFileWithThePrimaryUnavailable(): void
    {
        $this->expectException(NotStoredMediaException::class);
        $this->expectExceptionMessage('Media storage unavailable for "shared/file.txt"');

        $this->primary->markUnavailable();

        $this->createSUT()->read('shared/file.txt');
    }

    public function testReadFromThePrimaryDoesNotTouchTheFallback(): void
    {
        $this->primary->write('shared/file.txt', MediaStorageMother::localFile('hola'));
        $this->fallback->markUnavailable();

        $stream = $this->createSUT()->read('shared/file.txt');

        $this->assertSame('hola', stream_get_contents($stream));

        fclose($stream);
    }

    public function testExistsInTheFallbackWithThePrimaryUnavailable(): void
    {
        $this->fallback->write('shared/file.txt', MediaStorageMother::localFile('hola'));
        $this->primary->markUnavailable();

        $this->assertTrue($this->createSUT()->exists('shared/file.txt'));
    }

    public function testExistsAMissingFileWithThePrimaryUnavailable(): void
    {
        $this->primary->markUnavailable();

        $this->assertFalse($this->createSUT()->exists('shared/file.txt'));
    }

    public function testDeleteWithThePrimaryUnavailableAndNoPendingTask(): void
    {
        $this->primary->markUnavailable();

        try {
            $this->createSUT()->delete('shared/file.txt');

            $this->fail('A NotStoredMediaException was expected');
        } catch (NotStoredMediaException) {
        }

        $this->assertSame(0, $this->tasks->countPending());
        $this->assertSame([], $this->spySubscriber->events());
    }

    public function testDeleteAPendingFileWithThePrimaryUnavailable(): void
    {
        $this->fallback->write('shared/file.txt', MediaStorageMother::localFile('hola'));
        $this->tasks->save(StorageSyncTaskMother::forPath('shared/file.txt'));
        $this->primary->markUnavailable();

        $this->createSUT()->delete('shared/file.txt');

        $this->assertNull($this->tasks->find('shared/file.txt'));
        $this->assertFalse($this->fallback->exists('shared/file.txt'));
    }

    public function testDeleteWithThePrimaryAvailable(): void
    {
        $this->primary->write('shared/file.txt', MediaStorageMother::localFile('hola'));
        $this->fallback->write('shared/file.txt', MediaStorageMother::localFile('hola'));

        $this->createSUT()->delete('shared/file.txt');

        $this->assertFalse($this->primary->exists('shared/file.txt'));
        $this->assertFalse($this->fallback->exists('shared/file.txt'));
        $this->assertSame(0, $this->tasks->countPending());
    }

    public function testSameStorageAsPrimaryAndFallbackDelegates(): void
    {
        $this->primary->markUnavailable();

        $sut = new FallbackMediaStorage($this->primary, $this->primary, $this->tasks);

        try {
            $sut->write('shared/file.txt', MediaStorageMother::localFile('hola'));

            $this->fail('A NotStoredMediaException was expected');
        } catch (NotStoredMediaException) {
        }

        try {
            $sut->delete('shared/file.txt');

            $this->fail('A NotStoredMediaException was expected');
        } catch (NotStoredMediaException) {
        }

        $this->assertSame(0, $this->tasks->countPending());
        $this->assertSame([], $this->spySubscriber->events());
    }

    public function testStoreFallsBackToTheLocalDiskWithThePrimaryUnavailable(): void
    {
        $baseDir = sys_get_temp_dir() . '/library_fallback_test_' . uniqid();
        $this->primary->markUnavailable();

        $sut = new FallbackMediaStorage($this->primary, new LocalMediaStorage($baseDir), $this->tasks);

        $path = $sut->store(MediaId::generate(), null, 'txt', MediaStorageMother::localFile('hola'));

        $this->assertFileExists($baseDir . '/' . $path);
        $this->assertNotNull($this->tasks->find($path));
        $this->assertTrue($sut->exists($path));

        $stream = $sut->read($path);

        $this->assertSame('hola', stream_get_contents($stream));

        fclose($stream);

        $sut->delete($path);

        $this->assertFileDoesNotExist($baseDir . '/' . $path);
        $this->assertNull($this->tasks->find($path));

        rmdir(dirname($baseDir . '/' . $path));
        rmdir($baseDir);
    }

    private function createSUT(): FallbackMediaStorage
    {
        return new FallbackMediaStorage($this->primary, $this->fallback, $this->tasks);
    }
}
