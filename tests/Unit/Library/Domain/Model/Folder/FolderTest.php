<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Model\Folder;

use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Folder\FolderRegistered;
use App\Library\Domain\Model\Folder\FolderRemoved;
use App\Library\Domain\Model\Folder\FolderRenamed;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class FolderTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegisterPublishesTheEventAndKeepsTheNameAndTheAccount(): void
    {
        $accountId = AccountId::generate();

        $folder = FolderMother::fromNameAndAccount('Contratos', $accountId);

        $this->assertEquals('Contratos', $folder->name());
        $this->assertEquals('Contratos', (string) $folder);
        $this->assertNotNull($folder->accountId());
        $this->assertTrue($folder->accountId()->equals($accountId));

        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(FolderRegistered::class, $event);

        $rebuilt = FolderRegistered::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());

        $this->assertEquals('Contratos', $rebuilt->name());
        $this->assertTrue($rebuilt->folderId()->equals($folder->id()));
        $this->assertNotNull($rebuilt->accountId());
        $this->assertTrue($rebuilt->accountId()->equals($accountId));
    }

    public function testRegisterWithoutAccountSurvivesTheRebuild(): void
    {
        FolderMother::fromName('Contratos');

        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(FolderRegistered::class, $event);

        $rebuilt = FolderRegistered::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());

        $this->assertNull($rebuilt->accountId());
    }

    public function testRegisterWithoutParentIsNotASubfolder(): void
    {
        $folder = FolderMother::fromName('Contratos');

        $this->assertNull($folder->parentId());
        $this->assertFalse($folder->isSubfolder());

        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(FolderRegistered::class, $event);

        $rebuilt = FolderRegistered::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());

        $this->assertNull($rebuilt->parentId());
    }

    public function testRegisterWithParentKeepsTheParent(): void
    {
        $parent = FolderMother::fromName('Contratos');

        $subfolder = FolderMother::subfolderOf($parent, '2024');

        $this->assertTrue($subfolder->isSubfolder());
        $this->assertNotNull($subfolder->parentId());
        $this->assertTrue($subfolder->parentId()->equals($parent->id()));

        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(FolderRegistered::class, $event);

        $rebuilt = FolderRegistered::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());

        $this->assertNotNull($rebuilt->parentId());
        $this->assertTrue($rebuilt->parentId()->equals($parent->id()));
    }

    public function testRebuildFromAPayloadWithoutParentKeepsTheParentEmpty(): void
    {
        $folderId = FolderId::generate();

        $rebuilt = FolderRegistered::rebuildFromPayload(
            DomainEventId::generate(),
            new \DateTimeImmutable(),
            ['folderId' => (string) $folderId, 'name' => 'Contratos', 'accountId' => null],
        );

        $this->assertTrue($rebuilt->folderId()->equals($folderId));
        $this->assertNull($rebuilt->parentId());
    }

    public function testRenamePublishesTheEvent(): void
    {
        $folder = FolderMother::fromName('Contratos');

        $folder->rename('Facturas');

        $this->assertEquals('Facturas', $folder->name());

        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(FolderRenamed::class, $event);

        $rebuilt = FolderRenamed::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());

        $this->assertEquals('Facturas', $rebuilt->name());
        $this->assertTrue($rebuilt->folderId()->equals($folder->id()));
    }

    public function testRemovePublishesTheEvent(): void
    {
        $folder = FolderMother::from(FolderId::generate(), 'Contratos');

        $folder->remove();

        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(FolderRemoved::class, $event);

        $rebuilt = FolderRemoved::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());

        $this->assertEquals('Contratos', $rebuilt->name());
        $this->assertTrue($rebuilt->folderId()->equals($folder->id()));
    }
}
