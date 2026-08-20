<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Branding\Resolve;

use App\Security\Application\Branding\Resolve\ResolveBrandingService;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Branding\BrandingPalette;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryBrandingRepository;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingRepositoryMother;
use App\Tests\Unit\Shared\Infrastructure\Theme\FakeTheme;
use App\Tests\Unit\Shared\Infrastructure\Theme\ThemeRegistryMother;
use PHPUnit\Framework\TestCase;

class ResolveBrandingServiceTest extends TestCase
{
    public function testWithoutAccountItReturnsTheDefaultTokens(): void
    {
        $tokens = $this->createSUT(BrandingRepositoryMother::inMemory())->resolve(null);

        $this->assertSame('default', $tokens->themeSlug);
        $this->assertSame('#3E7490', $tokens->primary->value);
        $this->assertFalse($tokens->branded);
    }

    public function testAccountWithoutBrandingLooksExactlyLikeTheDefault(): void
    {
        $sut = $this->createSUT(BrandingRepositoryMother::inMemory());

        $this->assertEquals($sut->resolve(null), $sut->resolve(AccountId::generate()));
    }

    public function testAccountWithBrandingGetsItsOwnColours(): void
    {
        $repository = BrandingRepositoryMother::inMemory();

        $account = AccountMother::fromName('Acme');
        $repository->add(BrandingMother::brandedForAccount($account->id()));

        $tokens = $this->createSUT($repository)->resolve($account->id());

        $this->assertSame('#8A2432', $tokens->primary->value);
        $this->assertTrue($tokens->branded);
    }

    public function testTokensFallBackToTheThemeDefaultsForUnsetColours(): void
    {
        $repository = BrandingRepositoryMother::inMemory();

        $account = AccountMother::fromName('Acme');
        $branding = BrandingMother::forAccount($account->id());
        $branding->changeTheme('aurora');
        $repository->add($branding);

        $tokens = $this->createSUT($repository)->resolve($account->id());

        $this->assertSame('aurora', $tokens->themeSlug);
        $this->assertSame('#123456', $tokens->primary->value);
        $this->assertTrue($tokens->branded);
    }

    public function testRemovedBrandingFallsBackToTheDefault(): void
    {
        $repository = BrandingRepositoryMother::inMemory();

        $account = AccountMother::fromName('Acme');
        $branding = BrandingMother::brandedForAccount($account->id());
        $repository->add($branding);

        $branding->remove();
        $repository->add($branding);

        $tokens = $this->createSUT($repository)->resolve($account->id());

        $this->assertSame('#3E7490', $tokens->primary->value);
        $this->assertFalse($tokens->branded);
    }

    private function createSUT(InMemoryBrandingRepository $repository): ResolveBrandingService
    {
        return new ResolveBrandingService(
            $repository,
            ThemeRegistryMother::withThemes([new FakeTheme()]),
            new BrandingPalette(),
        );
    }
}
