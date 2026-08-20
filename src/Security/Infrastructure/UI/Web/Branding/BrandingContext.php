<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Branding;

use App\Security\Application\Branding\Resolve\BrandingResolver;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Branding\BrandingTokens;

class BrandingContext
{
    private ?AccountId $accountId = null;

    private ?BrandingTokens $tokens = null;

    public function __construct(private readonly BrandingResolver $resolver) {}

    public function useAccount(?AccountId $accountId): void
    {
        $this->accountId = $accountId;
        $this->tokens = null;
    }

    public function useTokens(BrandingTokens $tokens): void
    {
        $this->tokens = $tokens;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function tokens(): BrandingTokens
    {
        return $this->tokens ??= $this->resolver->resolve($this->accountId);
    }

    public function isBranded(): bool
    {
        return $this->tokens()->branded;
    }
}
