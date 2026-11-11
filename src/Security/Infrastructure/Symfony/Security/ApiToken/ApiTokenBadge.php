<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security\ApiToken;

use App\Security\Domain\Model\ApiToken\ApiTokenId;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

final class ApiTokenBadge implements BadgeInterface
{
    public const ATTRIBUTE = 'api_token_id';

    private ?ApiTokenId $apiTokenId = null;

    public function apiTokenId(): ?ApiTokenId
    {
        return $this->apiTokenId;
    }

    public function authenticatedWith(ApiTokenId $apiTokenId): void
    {
        $this->apiTokenId = $apiTokenId;
    }

    public function isResolved(): bool
    {
        return true;
    }
}
