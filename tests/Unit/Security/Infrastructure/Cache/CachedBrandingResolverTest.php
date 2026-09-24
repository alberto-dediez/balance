<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Cache;

use App\Security\Application\Branding\Resolve\ResolveBrandingService;
use App\Security\Domain\Event\Branding\InvalidateBrandingCacheSubscriber;
use App\Security\Domain\Model\Branding\BrandingPalette;
use App\Security\Domain\Model\Branding\BrandingRepository;
use App\Security\Infrastructure\Cache\CachedBrandingResolver;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingRepositoryMother;
use App\Tests\Unit\Shared\Infrastructure\Theme\ThemeRegistryMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class CachedBrandingResolverTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testItCachesTheResolvedTokensPerAccount(): void
    {
        $repository = BrandingRepositoryMother::inMemory();
        $cache = new ArrayAdapter();

        $account = AccountMother::fromName('Acme');
        $sut = new CachedBrandingResolver($this->inner($repository), $cache);

        $tokensBeforeBranding = $sut->resolve($account->id());

        $this->assertSame('#3E7490', $tokensBeforeBranding->primary->value);

        $repository->add(BrandingMother::brandedForAccount($account->id()));

        $tokensAfterBranding = $sut->resolve($account->id());

        $this->assertSame('#3E7490', $tokensAfterBranding->primary->value);
    }

    public function testTheSubscriberInvalidatesTheCachedTokens(): void
    {
        $repository = BrandingRepositoryMother::inMemory();
        $cache = new ArrayAdapter();

        new InvalidateBrandingCacheSubscriber($cache);

        $account = AccountMother::fromName('Acme');
        $sut = new CachedBrandingResolver($this->inner($repository), $cache);

        $this->assertSame('#3E7490', $sut->resolve($account->id())->primary->value);

        $repository->add(BrandingMother::brandedForAccount($account->id()));

        $this->assertSame('#8A2432', $sut->resolve($account->id())->primary->value);
    }

    public function testWithoutAccountItDoesNotTouchTheCache(): void
    {
        $cache = new ArrayAdapter();

        $sut = new CachedBrandingResolver($this->inner(BrandingRepositoryMother::inMemory()), $cache);

        $this->assertSame('#3E7490', $sut->resolve(null)->primary->value);
        $this->assertSame([], $cache->getValues());
    }

    private function inner(BrandingRepository $repository): ResolveBrandingService
    {
        return new ResolveBrandingService($repository, ThemeRegistryMother::withThemes(), new BrandingPalette());
    }
}
