<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\UI\Web\Component\Topbar;

use App\Notifications\Application\Notification\Unread\UnreadNotificationsProvider;
use App\Notifications\Domain\Model\Notification\Notification;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Topbar:Notifications', template: '@components/Topbar/Notifications.html.twig')]
final readonly class Notifications
{
    public function __construct(
        private UnreadNotificationsProvider $provider,
        private bool $internalNotificationsEnabled = true,
        private bool $liveNotificationsEnabled = true,
        private int $pollingSeconds = 30,
    ) {}

    public function enabled(): bool
    {
        return $this->internalNotificationsEnabled;
    }

    public function liveEnabled(): bool
    {
        return $this->internalNotificationsEnabled && $this->liveNotificationsEnabled;
    }

    public function pollingSeconds(): int
    {
        return max(5, $this->pollingSeconds);
    }

    public function count(): int
    {
        return $this->provider->count();
    }

    /**
     * @return list<Notification>
     */
    public function latest(): array
    {
        return $this->provider->latest(5);
    }
}
