<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialitySettings;

use App\Materiality\Application\MaterialitySettings\MaterialitySettingsAuthorizer;
use App\Materiality\Application\MaterialitySettings\Update\UpdateMaterialitySettingsRequest;
use App\Materiality\Application\MaterialitySettings\Update\UpdateMaterialitySettingsService;
use App\Materiality\Domain\Model\MaterialitySettings\InvalidRelevanceThresholdException;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsRegistered;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsRepository;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsUpdated;
use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialitySettingsRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateMaterialitySettingsServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testFirstSaveRegistersTheSettings(): void
    {
        $repository = new InMemoryMaterialitySettingsRepository();

        $response = $this->createSUT($repository)(new UpdateMaterialitySettingsRequest(40, false), UserMother::adminUser());

        $persisted = $repository->findCurrent();

        $this->assertNotNull($persisted);
        $this->assertTrue($persisted->id()->equals($response->materialitySettingsId));
        $this->assertSame(40, $persisted->relevanceThresholdPercent());
        $this->assertFalse($persisted->highMagnitudeForcesInclusion());
        $this->assertInstanceOf(MaterialitySettingsRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testSecondSaveUpdatesTheSameSettings(): void
    {
        $repository = new InMemoryMaterialitySettingsRepository();
        $first = $this->createSUT($repository)(new UpdateMaterialitySettingsRequest(40, false), UserMother::adminUser());

        $second = $this->createSUT($repository)(new UpdateMaterialitySettingsRequest(65, true), UserMother::adminUser());

        $this->assertTrue($first->materialitySettingsId->equals($second->materialitySettingsId));
        $this->assertSame(65, $repository->findCurrent()?->relevanceThresholdPercent());
        $this->assertInstanceOf(MaterialitySettingsUpdated::class, $this->spySubscriber->lastEvent());
    }

    public function testInvalidThresholdThrows(): void
    {
        $this->expectException(InvalidRelevanceThresholdException::class);

        $this->createSUT(new InMemoryMaterialitySettingsRepository())(new UpdateMaterialitySettingsRequest(150, true), UserMother::adminUser());
    }

    public function testOwnerCannotUpdate(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT(new InMemoryMaterialitySettingsRepository())(new UpdateMaterialitySettingsRequest(40, true), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->createSUT(new InMemoryMaterialitySettingsRepository())->isHandled(new UpdateMaterialitySettingsRequest(40, true)));
    }

    private function createSUT(MaterialitySettingsRepository $repository): UpdateMaterialitySettingsService
    {
        return new UpdateMaterialitySettingsService(
            materialitySettingsRepository: $repository,
            authorizer: new MaterialitySettingsAuthorizer(),
        );
    }
}
