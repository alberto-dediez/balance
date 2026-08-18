<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

interface PayoutNumberGenerator
{
    public function next(\DateTimeImmutable $on): string;
}
