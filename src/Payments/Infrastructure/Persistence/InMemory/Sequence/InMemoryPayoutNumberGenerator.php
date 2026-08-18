<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Persistence\InMemory\Sequence;

use App\Payments\Domain\Model\Payout\PayoutNumberGenerator;
use App\Payments\Domain\Model\Payout\PayoutRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryPayoutNumberGenerator implements PayoutNumberGenerator
{
    public function __construct(private PayoutRepository $payoutRepository) {}

    public function next(\DateTimeImmutable $on): string
    {
        $year = (int) $on->format('Y');

        $numbers = $this->payoutRepository->numbersOfYear($year);

        $sequence = [] === $numbers ? 1 : ((int) substr((string) end($numbers), -4)) + 1;

        return sprintf('%d/%04d', $year, $sequence);
    }
}
