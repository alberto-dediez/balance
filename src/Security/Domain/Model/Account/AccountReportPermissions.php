<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Account;

final class AccountReportPermissions
{
    public function __construct(
        private bool $canEditTexts = false,
        private bool $autoReview = true,
    ) {}

    public function canEditTexts(): bool
    {
        return $this->canEditTexts;
    }

    public function autoReview(): bool
    {
        return $this->autoReview;
    }

    public function equals(self $other): bool
    {
        return $this->canEditTexts === $other->canEditTexts()
            && $this->autoReview === $other->autoReview();
    }
}
