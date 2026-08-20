<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Cache;

use App\Security\Application\Branding\Resolve\BrandingResolver;
use App\Security\Application\Branding\Resolve\ResolveBrandingService;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Branding\BrandingTokens;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Contracts\Cache\CacheInterface;

#[AsDecorator(decorates: ResolveBrandingService::class)]
class CachedBrandingResolver implements BrandingResolver
{
    public function __construct(private BrandingResolver $inner, private CacheInterface $cache) {}

    public function resolve(?AccountId $accountId): BrandingTokens
    {
        if (!$accountId instanceof AccountId) {
            return $this->inner->resolve(null);
        }

        return $this->cache->get(
            self::cacheKey($accountId),
            function (CacheItemInterface $item) use ($accountId): BrandingTokens {
                $item->expiresAfter(3600);

                return $this->inner->resolve($accountId);
            },
        );
    }

    public static function cacheKey(AccountId $accountId): string
    {
        return 'branding.' . $accountId;
    }
}
