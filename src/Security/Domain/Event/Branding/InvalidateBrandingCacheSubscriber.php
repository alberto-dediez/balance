<?php

declare(strict_types=1);

namespace App\Security\Domain\Event\Branding;

use App\Security\Domain\Model\Branding\BrandingChanged;
use App\Security\Domain\Model\Branding\BrandingRegistered;
use App\Security\Domain\Model\Branding\BrandingRemoved;
use App\Security\Domain\Model\Branding\BrandingThemeChanged;
use App\Security\Infrastructure\Cache\CachedBrandingResolver;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use Psr\Cache\CacheItemPoolInterface;

class InvalidateBrandingCacheSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private CacheItemPoolInterface $cache)
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof BrandingRegistered || $event instanceof BrandingChanged || $event instanceof BrandingThemeChanged || $event instanceof BrandingRemoved);

        $this->cache->deleteItem(CachedBrandingResolver::cacheKey($event->accountId()));
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof BrandingRegistered
            || $event instanceof BrandingChanged
            || $event instanceof BrandingThemeChanged
            || $event instanceof BrandingRemoved;
    }
}
