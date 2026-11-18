<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Twig;

use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\Live\LiveTopics;
use Symfony\Component\Mercure\HubInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class LiveExtension extends AbstractExtension
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly LoggedUser $loggedUser,
        private readonly LiveTopics $topics,
        private readonly bool $enabled,
    ) {}

    /** @return list<TwigFunction>  */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('live_hub_url', $this->hubUrl(...)),
        ];
    }

    public function hubUrl(): string
    {
        if (!$this->enabled) {
            return '';
        }

        $user = $this->loggedUser->getLoggedUserOrNull();

        if (!$user instanceof User) {
            return '';
        }

        $query = array_map(
            static fn(string $topic): string => 'topic=' . rawurlencode($topic),
            $this->topics->for($user),
        );

        return $this->hub->getPublicUrl() . '?' . implode('&', $query);
    }
}
