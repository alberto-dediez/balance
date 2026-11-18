<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\UI\Web;

use App\Assistant\Application\AssistantSettings;
use App\Assistant\Application\Conversation\ConversationAuthorizer;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;

final readonly class AssistantAccess
{
    public function __construct(
        private AssistantSettings $settings,
        private ConversationAuthorizer $authorizer,
        private LoggedUser $loggedUser,
    ) {}

    public function isGranted(): bool
    {
        if (!$this->settings->enabled) {
            return false;
        }

        $user = $this->loggedUser->getLoggedUserOrNull();

        if (!$user instanceof User) {
            return false;
        }

        if ($this->loggedUser->isImpersonated()) {
            return false;
        }

        return $this->authorizer->canDoThat(new Conversation(ConversationId::generate()), Action::Register, $user);
    }
}
