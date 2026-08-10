<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

class ContributionAmountTooLowException extends \RuntimeException
{
    public function __construct(float $totalAmount, float $minimumAmount)
    {
        parent::__construct(
            sprintf(
                'La aportación asciende a %.2f € y el mínimo que se puede cobrar es %.2f €.',
                $totalAmount,
                $minimumAmount,
            ),
            422,
        );
    }
}
