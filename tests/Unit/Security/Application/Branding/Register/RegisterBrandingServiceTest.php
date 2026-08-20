<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Branding\Register;

use App\Security\Application\Branding\Register\RegisterBrandingRequest;
use App\Security\Application\Branding\Register\RegisterBrandingService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Branding\BrandingFilter;
use App\Security\Domain\Model\Branding\BrandingRegistered;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryBrandingRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\Branding\BrandingAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterBrandingServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testItRegistersABrandingForTheAccount(): void
    {
        $repository = BrandingRepositoryMother::inMemory();

        $account = AccountMother::fromName('Acme');

        $result = $this->createSUT($repository)(new RegisterBrandingRequest($account->id()), UserMother::adminUser());

        $branding = $repository->find($result->brandingId);

        $this->assertNotNull($branding);
        $this->assertTrue($account->id()->equals($branding->accountId()));
        $this->assertSame('default', $branding->themeSlug());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(BrandingRegistered::class, $lastEvent);
    }

    public function testItIsIdempotentPerAccount(): void
    {
        $repository = BrandingRepositoryMother::inMemory();

        $account = AccountMother::fromName('Acme');
        $sut = $this->createSUT($repository);
        $user = UserMother::adminUser();

        $first = $sut(new RegisterBrandingRequest($account->id()), $user);
        $second = $sut(new RegisterBrandingRequest($account->id()), $user);

        $this->assertTrue($first->brandingId->equals($second->brandingId));
        $this->assertCount(1, $repository->findByFilter(new BrandingFilter()));
    }

    public function testOwnerOfAnotherAccountCannotRegister(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = BrandingRepositoryMother::inMemory();

        $account = AccountMother::fromName('Acme');
        $stranger = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountMother::fromName('Other')->id());

        $this->createSUT($repository)(new RegisterBrandingRequest($account->id()), $stranger);
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(BrandingRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled(new RegisterBrandingRequest(AccountMother::fromName('Acme')->id())));
    }

    private function createSUT(InMemoryBrandingRepository $repository): RegisterBrandingService
    {
        return new RegisterBrandingService($repository, BrandingAuthorizerMother::create());
    }
}
