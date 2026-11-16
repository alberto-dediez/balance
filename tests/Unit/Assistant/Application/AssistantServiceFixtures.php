<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application;

use App\Assistant\Application\ActionPlan\Approve\ApproveActionPlanService;
use App\Assistant\Application\ActionPlan\Discard\DiscardActionPlanService;
use App\Assistant\Application\ActionPlan\Execute\ExecuteActionPlanService;
use App\Assistant\Application\ActionPlan\Propose\ProposeActionPlanService;
use App\Assistant\Application\AssistantSettings;
use App\Assistant\Application\Conversation\Answer\AnswerMessageService;
use App\Assistant\Application\Conversation\SendMessage\SendMessageService;
use App\Assistant\Application\Conversation\Start\StartConversationService;
use App\Assistant\Application\Engine\AssistantPrompts;
use App\Assistant\Application\Engine\ConversationTranscript;
use App\Assistant\Application\Engine\DailyBudget;
use App\Assistant\Application\Engine\PlanStepVerifier;
use App\Assistant\Application\Engine\Toolbox;
use App\Assistant\Application\Engine\ToolRunner;
use App\Assistant\Application\Engine\ToolSchemaBuilder;
use App\Assistant\Infrastructure\Persistence\InMemory\Repository\InMemoryActionPlanRepository;
use App\Assistant\Infrastructure\Persistence\InMemory\Repository\InMemoryConversationRepository;
use App\Assistant\Infrastructure\Storage\InMemoryConversationAttachmentStorage;
use App\Shared\Application\Assistant\CurrentAssistantExecution;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Infrastructure\Ai\InMemoryAiAgentClient;
use App\Shared\Infrastructure\Assistant\ResettableCurrentAssistantExecution;
use App\Tests\Unit\Assistant\Application\ActionPlan\ActionPlanAuthorizerMother;
use App\Tests\Unit\Assistant\Application\Conversation\ConversationAuthorizerMother;
use App\Tests\Unit\Assistant\Application\Engine\FakeAssistantTool;
use App\Tests\Unit\Notifications\Application\Notification\Send\SpyDeferredCommandHandler;
use App\Tests\Unit\Shared\Application\Clock\FixedClock;
use App\Tests\Unit\Shared\Application\Live\SpyLiveUpdates;
use Psr\Log\NullLogger;

trait AssistantServiceFixtures
{
    private InMemoryConversationRepository $conversations;

    private InMemoryActionPlanRepository $plans;

    private InMemoryConversationAttachmentStorage $attachmentStorage;

    private InMemoryAiAgentClient $client;

    private SpyLiveUpdates $liveUpdates;

    private SpyDeferredCommandHandler $deferred;

    private CurrentAssistantExecution $execution;

    private FixedClock $clock;

    private AssistantSettings $settings;

    /** @var list<FakeAssistantTool> */
    private array $tools = [];

    private function setUpAssistant(?AssistantSettings $settings = null, ?\DateTimeImmutable $now = null): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();
        $this->deferred = new SpyDeferredCommandHandler();
        CommandBusDispatcher::instance()->subscribe($this->deferred);

        $this->conversations = new InMemoryConversationRepository();
        $this->plans = new InMemoryActionPlanRepository();
        $this->attachmentStorage = new InMemoryConversationAttachmentStorage();
        $this->client = new InMemoryAiAgentClient();
        $this->liveUpdates = new SpyLiveUpdates();
        $this->execution = new ResettableCurrentAssistantExecution();
        $this->clock = new FixedClock($now ?? new \DateTimeImmutable('2026-11-11 10:00:00'));
        $this->settings = $settings ?? new AssistantSettings();
    }

    private function toolbox(): Toolbox
    {
        return new Toolbox($this->tools);
    }

    private function runner(): ToolRunner
    {
        return new ToolRunner($this->toolbox(), new NullLogger());
    }

    private function budget(): DailyBudget
    {
        return new DailyBudget($this->conversations, $this->plans, $this->settings, $this->clock);
    }

    private function discardService(): DiscardActionPlanService
    {
        return new DiscardActionPlanService($this->plans, ActionPlanAuthorizerMother::create(), $this->clock);
    }

    private function proposeService(): ProposeActionPlanService
    {
        return new ProposeActionPlanService($this->plans, $this->conversations, ActionPlanAuthorizerMother::create(), ConversationAuthorizerMother::create(), $this->toolbox(), $this->clock);
    }

    private function sendService(): SendMessageService
    {
        return new SendMessageService(
            $this->conversations,
            $this->plans,
            ConversationAuthorizerMother::create(),
            new StartConversationService($this->conversations, ConversationAuthorizerMother::create(), $this->clock),
            $this->discardService(),
            $this->budget(),
            $this->settings,
            $this->attachmentStorage,
            $this->clock,
        );
    }

    private function answerService(): AnswerMessageService
    {
        return new AnswerMessageService(
            $this->conversations,
            $this->plans,
            ConversationAuthorizerMother::create(),
            $this->client,
            $this->toolbox(),
            $this->runner(),
            new ToolSchemaBuilder(),
            $this->transcript(),
            new AssistantPrompts(),
            $this->proposeService(),
            $this->settings,
            $this->liveUpdates,
            $this->clock,
            new NullLogger(),
        );
    }

    private function transcript(): ConversationTranscript
    {
        return new ConversationTranscript($this->attachmentStorage, $this->settings);
    }

    private function approveService(): ApproveActionPlanService
    {
        return new ApproveActionPlanService($this->plans, $this->conversations, ActionPlanAuthorizerMother::create(), $this->settings, $this->budget(), $this->clock);
    }

    private function executeService(): ExecuteActionPlanService
    {
        return new ExecuteActionPlanService(
            $this->plans,
            $this->conversations,
            ActionPlanAuthorizerMother::create(),
            $this->client,
            $this->toolbox(),
            $this->runner(),
            new ToolSchemaBuilder(),
            new PlanStepVerifier(),
            new AssistantPrompts(),
            $this->execution,
            $this->settings,
            $this->liveUpdates,
            $this->clock,
            new NullLogger(),
        );
    }
}
