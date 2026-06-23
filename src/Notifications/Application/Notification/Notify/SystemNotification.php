<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Notify;

use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;

/**
 * Describes a single system notification to be routed through a single channel.
 *
 * The content is provided either as a plain {@see $body} or as a {@see $template}
 * (with its {@see $params}). When a template is provided it takes precedence over
 * the body. At least one of them must be present.
 *
 * Both the {@see $subject} (a translation key, resolved with {@see $subjectParams})
 * and the template are localized to the recipient's preferred locale when the
 * notification is dispatched, so callers must pass them untranslated.
 */
final class SystemNotification
{
    /**
     * @param array<string,mixed> $subjectParams
     * @param array<string,mixed> $params
     */
    public function __construct(
        public readonly UserId $recipientId,
        public readonly NotificationChannel $channel,
        public readonly string $subject,
        public readonly ?string $body = null,
        public readonly ?string $template = null,
        public readonly array $params = [],
        public readonly ?AccountId $accountId = null,
        public readonly array $subjectParams = [],
    ) {
        if (null === $this->template && null === $this->body) {
            throw new \InvalidArgumentException('A system notification requires either a body or a template.');
        }
    }

    public function hasTemplate(): bool
    {
        return null !== $this->template;
    }
}
