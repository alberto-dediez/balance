<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Event;

use App\Security\Application\ApiToken\ApiTokenPolicy;
use App\Security\Domain\Model\ApiToken\ApiTokenId;
use App\Security\Domain\Model\ApiToken\ApiTokenRepository;
use App\Security\Infrastructure\Symfony\Security\ApiToken\ApiTokenBadge;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: Events::JWT_DECODED)]
class ApiTokenJwtDecodedListener
{
    public function __construct(
        private readonly ApiTokenRepository $repository,
        private readonly ApiTokenPolicy $policy,
    ) {}

    public function __invoke(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();

        if (!array_key_exists(ApiTokenBadge::ATTRIBUTE, $payload)) {
            return;
        }

        $apiTokenId = $payload[ApiTokenBadge::ATTRIBUTE];

        if (!is_string($apiTokenId) || '' === $apiTokenId) {
            $event->markAsInvalid();

            return;
        }

        $apiToken = $this->repository->find(new ApiTokenId($apiTokenId));

        if (null === $apiToken || !$this->policy->isUsable($apiToken, new \DateTimeImmutable('NOW'))) {
            $event->markAsInvalid();
        }
    }
}
