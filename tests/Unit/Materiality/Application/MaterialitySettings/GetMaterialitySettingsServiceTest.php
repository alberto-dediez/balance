<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialitySettings;

use App\Materiality\Application\MaterialitySettings\Get\GetMaterialitySettingsRequest;
use App\Materiality\Application\MaterialitySettings\Get\GetMaterialitySettingsService;
use App\Materiality\Application\MaterialitySettings\MaterialitySettingsAuthorizer;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsRepository;
use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialitySettingsRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetMaterialitySettingsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testWithoutSettingsReturnsTheDefaultRule(): void
    {
        $response = $this->createSUT(new InMemoryMaterialitySettingsRepository())(new GetMaterialitySettingsRequest(), UserMother::adminUser());

        $this->assertSame(50, $response->relevanceThresholdPercent);
        $this->assertTrue($response->highMagnitudeForcesInclusion);
        $this->assertFalse($response->persisted);
    }

    public function testReturnsTheStoredSettings(): void
    {
        $repository = new InMemoryMaterialitySettingsRepository();
        $repository->add(MaterialitySettingsMother::withThreshold(35, false));

        $response = $this->createSUT($repository)(new GetMaterialitySettingsRequest(), UserMother::adminUser());

        $this->assertSame(35, $response->relevanceThresholdPercent);
        $this->assertFalse($response->highMagnitudeForcesInclusion);
        $this->assertTrue($response->persisted);
    }

    public function testOwnerCannotView(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT(new InMemoryMaterialitySettingsRepository())(new GetMaterialitySettingsRequest(), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    private function createSUT(MaterialitySettingsRepository $repository): GetMaterialitySettingsService
    {
        return new GetMaterialitySettingsService(
            materialitySettingsRepository: $repository,
            authorizer: new MaterialitySettingsAuthorizer(),
        );
    }
}
