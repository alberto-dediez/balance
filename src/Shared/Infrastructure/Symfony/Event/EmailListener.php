<?php

namespace App\Shared\Infrastructure\Symfony\Event;

use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[When(env: 'test')]
#[AsEventListener(event: SentMessageEvent::class)]
class EmailListener
{
    private static array $messages = [];

    public function __invoke(SentMessageEvent $event): void
    {
        self::$messages[] = $event->getMessage()->getOriginalMessage();
    }

    /**
     * @return RawMessage[]
     */
    public function getMessages(): array
    {
        return self::$messages;
    }

    public function reset(): void
    {
        self::$messages = [];
    }
}