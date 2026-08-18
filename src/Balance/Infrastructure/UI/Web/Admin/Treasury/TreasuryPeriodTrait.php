<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\Treasury;

use Symfony\Component\HttpFoundation\Request;

trait TreasuryPeriodTrait
{
    /** @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable} */
    private function period(Request $request): array
    {
        $now = new \DateTimeImmutable('NOW');

        $from = $this->dateFrom($request, 'from') ?? $now->modify('first day of this month')->setTime(0, 0);
        $to = $this->dateFrom($request, 'to') ?? $now->modify('last day of this month')->setTime(23, 59, 59);

        return [$from, $to];
    }

    private function dateFrom(Request $request, string $key): ?\DateTimeImmutable
    {
        $value = $request->query->get($key);

        if (!is_string($value) || '' === $value) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
