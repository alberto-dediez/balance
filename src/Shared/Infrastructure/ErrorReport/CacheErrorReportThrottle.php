<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErrorReport;

use App\Shared\Domain\Model\ErrorReport\ErrorReportThrottle;
use Psr\Cache\CacheItemPoolInterface;

class CacheErrorReportThrottle implements ErrorReportThrottle
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly int $throttleMinutes,
    ) {}

    public function shouldNotify(string $fingerprint): bool
    {
        if ($this->throttleMinutes <= 0) {
            return true;
        }

        try {
            $item = $this->cache->getItem(sprintf('error_report_%s', $fingerprint));

            if ($item->isHit()) {
                return false;
            }

            $item->set(true);
            $item->expiresAfter($this->throttleMinutes * 60);

            $this->cache->save($item);
        } catch (\Throwable) {
            return true;
        }

        return true;
    }
}
