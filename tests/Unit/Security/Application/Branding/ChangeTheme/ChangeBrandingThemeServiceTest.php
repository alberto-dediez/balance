<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Branding\ChangeTheme;

use App\Security\Application\Branding\ChangeTheme\ChangeBrandingThemeRequest;
use App\Security\Application\Branding\ChangeTheme\ChangeBrandingThemeService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Branding\BrandingThemeChanged;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryBrandingRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\Branding\BrandingAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Theme\FakeTheme;
use App\Tests\Unit\Shared\Infrastructure\Theme\ThemeRegistryMother;
use PHPUnit\Framework\TestCase;

class ChangeBrandingThemeServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testSuperAdminChangesTheTheme(): void
    {
        $repository = BrandingRepositoryMother::inMemory();

        $branding = BrandingMother::forAccount(AccountMother::fromName('Acme')->id());
        $repository->add($branding);

        $result = $this->createSUT($repository)(
            new ChangeBrandingThemeRequest($branding->id(), 'aurora'),
            UserMother::fromUsernameAndRole('superadmin', Role::SuperAdmin),
        );

        $this->assertSame('aurora', $result->themeSlug);
        $this->assertInstanceOf(BrandingThemeChanged::class, $this->spySubscriber->lastEvent());
    }

    public function testUnknownThemeFallsBackToTheDefaultSlug(): void
    {
        $repository = BrandingRepositoryMother::inMemory();

        $branding = BrandingMother::forAccount(AccountMother::fromName('Acme')->id());
        $branding->changeTheme('aurora');
        $repository->add($branding);

        $result = $this->createSUT($repository)(
            new ChangeBrandingThemeRequest($branding->id(), 'does-not-exist'),
            UserMother::fromUsernameAndRole('superadmin', Role::SuperAdmin),
        );

        $this->assertSame('default', $result->themeSlug);
    }

    public function testPlainUserCannotChangeTheTheme(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = BrandingRepositoryMother::inMemory();

        $branding = BrandingMother::forAccount(AccountMother::fromName('Acme')->id());
        $repository->add($branding);

        $this->createSUT($repository)(
            new ChangeBrandingThemeRequest($branding->id(), 'aurora'),
            UserMother::fromUsernameAndRole('plain', Role::User),
        );
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(BrandingRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled(new ChangeBrandingThemeRequest(BrandingMother::random()->id(), 'aurora')));
    }

    private function createSUT(InMemoryBrandingRepository $repository): ChangeBrandingThemeService
    {
        return new ChangeBrandingThemeService(
            $repository,
            BrandingAuthorizerMother::create(),
            ThemeRegistryMother::withThemes([new FakeTheme()]),
        );
    }
}
