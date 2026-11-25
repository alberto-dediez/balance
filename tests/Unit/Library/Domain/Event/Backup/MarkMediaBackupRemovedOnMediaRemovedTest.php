<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Event\Backup;

use App\Library\Domain\Event\Backup\MarkMediaBackupRemovedOnMediaRemoved;
use App\Library\Domain\Model\Backup\MediaBackupFailed;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaRemoved;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaBackupRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Library\Domain\Model\Backup\MediaBackupMother;
use PHPUnit\Framework\TestCase;

class MarkMediaBackupRemovedOnMediaRemovedTest extends TestCase
{
    private InMemoryMediaBackupRepository $backups;

    private MarkMediaBackupRemovedOnMediaRemoved $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->backups = new InMemoryMediaBackupRepository();
        $this->sut = new MarkMediaBackupRemovedOnMediaRemoved($this->backups);
    }

    public function testMarksTheBackupAsRemoved(): void
    {
        $this->backups->save(MediaBackupMother::forPath('shared/file.png'));
        $occurredOn = new \DateTimeImmutable('2026-11-24 10:00:00');

        DomainEventPublisher::instance()->publish(new MediaRemoved(MediaId::generate(), 'shared/file.png', occurredOn: $occurredOn));

        $this->assertEquals($occurredOn, $this->backups->find('shared/file.png')?->removedAt());
    }

    public function testDoesNothingWithoutBackup(): void
    {
        $this->sut->handle(new MediaRemoved(MediaId::generate(), 'shared/file.png'));

        $this->assertNull($this->backups->find('shared/file.png'));
    }

    public function testDoesNothingWithAnEmptyPath(): void
    {
        $this->backups->save(MediaBackupMother::forPath(''));

        $this->sut->handle(new MediaRemoved(MediaId::generate()));

        $this->assertNull($this->backups->find('')?->removedAt());
    }

    public function testIsSubscribedOnlyToMediaRemoved(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo(new MediaRemoved(MediaId::generate(), 'shared/file.png')));
        $this->assertFalse($this->sut->isSubscribedTo(new MediaBackupFailed(1, 'error')));
    }
}
