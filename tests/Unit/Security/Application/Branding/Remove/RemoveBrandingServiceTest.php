<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Branding\Remove;

use App\Security\Application\Branding\Remove\RemoveBrandingRequest;
use App\Security\Application\Branding\Remove\RemoveBrandingService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Branding\BrandingRemoved;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryBrandingRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\Branding\BrandingAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveBrandingServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testItRemovesTheBranding(): void
    {
        $repository = BrandingRepositoryMother::inMemory();

        $branding = BrandingMother::forAccount(AccountMother::fromName('Acme')->id());
        $repository->add($branding);

        $this->createSUT($repository)(new RemoveBrandingRequest($branding->id()), UserMother::adminUser());

        $this->assertNull($repository->find($branding->id()));
        $this->assertInstanceOf(BrandingRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testOwnerOfAnotherAccountCannotRemove(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = BrandingRepositoryMother::inMemory();

        $branding = BrandingMother::forAccount(AccountMother::fromName('Acme')->id());
        $repository->add($branding);

        $stranger = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountMother::fromName('Other')->id());

        $this->createSUT($repository)(new RemoveBrandingRequest($branding->id()), $stranger);
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(BrandingRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled(new RemoveBrandingRequest(BrandingMother::random()->id())));
    }

    private function createSUT(InMemoryBrandingRepository $repository): RemoveBrandingService
    {
        return new RemoveBrandingService($repository, BrandingAuthorizerMother::create());
    }
}
