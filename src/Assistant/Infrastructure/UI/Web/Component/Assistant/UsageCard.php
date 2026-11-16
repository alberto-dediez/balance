<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\UI\Web\Component\Assistant;

use App\Assistant\Application\AssistantSettings;
use App\Assistant\Application\Conversation\ConversationAuthorizer;
use App\Assistant\Application\Usage\View\ViewAssistantUsageRequest;
use App\Assistant\Application\Usage\View\ViewAssistantUsageResponse;
use App\Assistant\Application\Usage\View\ViewAssistantUsageService;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\Period\DailyPeriod;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Assistant:UsageCard', template: '@components/Assistant/UsageCard.html.twig')]
final class UsageCard
{
    public DailyPeriod $period;

    public function __construct(
        private readonly AssistantSettings $settings,
        private readonly ConversationAuthorizer $authorizer,
        private readonly ViewAssistantUsageService $viewUsage,
        private readonly LoggedUser $loggedUser,
    ) {}

    public function enabled(): bool
    {
        $user = $this->loggedUser->getLoggedUserOrNull();

        return $this->settings->enabled && $user instanceof User && $this->authorizer->canDoThat(new Conversation(ConversationId::generate()), Action::Admin, $user);
    }

    public function usage(): ViewAssistantUsageResponse
    {
        return ($this->viewUsage)(new ViewAssistantUsageRequest($this->period), ($this->loggedUser)());
    }
}
