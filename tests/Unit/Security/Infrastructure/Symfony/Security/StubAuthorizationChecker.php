<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Symfony\Security;

use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class StubAuthorizationChecker implements AuthorizationCheckerInterface
{
    public function __construct(private readonly bool $granted) {}

    public function isGranted(mixed $attribute, mixed $subject = null, ?AccessDecision $accessDecision = null): bool
    {
        return $this->granted;
    }
}
