<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Assistant;

use App\Assistant\Domain\Model\ActionPlan\ActionPlan;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanFilter;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanRepository;
use App\Assistant\Domain\Model\AssistantUsage;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\ConversationFilter;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Assistant\Domain\Model\Conversation\MessageKind;
use App\Assistant\Infrastructure\Persistence\InMemory\Repository\InMemoryActionPlanRepository;
use App\Assistant\Infrastructure\Persistence\InMemory\Repository\InMemoryConversationRepository;
use App\Assistant\Infrastructure\Storage\InMemoryConversationAttachmentStorage;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\Ai\Agent\AiAgentBlock;
use App\Shared\Application\Ai\Agent\AiAgentMessage;
use App\Shared\Application\Ai\Agent\AiAgentRequest;
use App\Shared\Application\Ai\Agent\AiAgentRole;
use App\Shared\Application\Ai\Agent\AiAgentStep;
use App\Shared\Application\Ai\Agent\AiStopReason;
use App\Shared\Application\Ai\Agent\AiToolCall;
use App\Shared\Application\Ai\AiUsage;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Infrastructure\Ai\InMemoryAiAgentClient;
use App\Shared\Infrastructure\Messenger\DeferredMessage;
use App\Shared\Infrastructure\Messenger\DeferredMessageHandler;
use App\Shared\Infrastructure\Symfony\Event\KernelRequestListener;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\Assistant\Domain\Model\ActionPlan\ActionPlanMother;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class AssistantContext implements Context
{
    private AcceptanceClient $client;

    private int $calls = 0;

    public function __construct(KernelBrowser $kernelBrowser)
    {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    #[AfterScenario]
    public function after(AfterScenarioScope $scope): void
    {
        $this->getConversationRepository()->clear();
        $this->getPlanRepository()->clear();
        $this->getAgentClient()->reset();
        $this->getAttachmentStorage()->reset();
    }

    #[Given('the assistant will answer with text :text')]
    public function theAssistantWillAnswerWithText(string $text): void
    {
        $this->getAgentClient()->enqueueText($text);
    }

    #[Given('the assistant will call the tool :tool with:')]
    public function theAssistantWillCallTheToolWith(string $tool, PyStringNode $input): void
    {
        $data = json_decode($input->getRaw(), true);

        Assert::assertIsArray($data, 'The tool input must be a JSON object');

        /* @var array<string, mixed> $data */
        $this->getAgentClient()->enqueueToolCalls(new AiToolCall(sprintf('toolu_%d', ++$this->calls), $tool, $data));
    }

    #[Given('the assistant will call the tool :tool with the references of the previous step and:')]
    public function theAssistantWillCallTheToolWithTheReferencesOfThePreviousStep(string $tool, PyStringNode $input): void
    {
        $id = sprintf('toolu_%d', ++$this->calls);
        $raw = $input->getRaw();

        $this->getAgentClient()->enqueueUsing(static function (AiAgentRequest $request) use ($tool, $id, $raw): AiAgentStep {
            $references = [];
            $last = $request->messages[count($request->messages) - 1] ?? null;

            foreach ($last->blocks ?? [] as $block) {
                $content = json_decode($block->text, true);

                if (is_array($content) && is_array($content['references'] ?? null)) {
                    $references = $content['references'];
                }
            }

            $data = json_decode((string) preg_replace_callback('/@(\w+)/', static fn(array $match): string => (string) ($references[$match[1]] ?? $match[0]), $raw), true);

            Assert::assertIsArray($data, 'The tool input must be a JSON object');

            /* @var array<string, mixed> $data */
            return new AiAgentStep(
                new AiAgentMessage(AiAgentRole::Assistant, [AiAgentBlock::toolUse($id, $tool, $data)]),
                AiUsage::none(),
                AiStopReason::ToolUse,
            );
        });
    }

    #[When('the assistant processes its pending work')]
    public function theAssistantProcessesItsPendingWork(): void
    {
        $container = $this->client->getContainer();
        $container->get(KernelRequestListener::class);
        $transport = $container->get('messenger.transport.async_high');
        $handler = $container->get(DeferredMessageHandler::class);

        Assert::assertInstanceOf(InMemoryTransport::class, $transport);

        DomainEventPublisher::instance()->unlock();

        $processed = 0;

        do {
            $envelopes = $transport->getSent();
            $transport->reset();

            foreach ($envelopes as $envelope) {
                $message = $envelope->getMessage();

                if ($message instanceof DeferredMessage) {
                    $handler($message);
                }
            }

            $processed += count($envelopes);
        } while ([] !== $envelopes && $processed < 50);
    }

    #[Given('conversation :title of user :username exists')]
    #[Given('conversation :title with id :id of user :username exists')]
    public function conversationOfUserExists(string $title, string $username, ?string $id = null): void
    {
        $user = $this->user($username);

        DomainEventPublisher::instance()->lock();

        $conversation = new Conversation(null === $id ? ConversationId::generate() : new ConversationId($id));
        $conversation->start($user->id(), new \DateTimeImmutable());
        $conversation->addUserMessage(MessageId::generate(), $title, '', '', 'Europe/Madrid', new \DateTimeImmutable());
        $this->getConversationRepository()->add($conversation);

        DomainEventPublisher::instance()->clearPending();
    }

    #[Given('user :username has spent :amount USD on the assistant today')]
    public function userHasSpentOnTheAssistantToday(string $username, string $amount): void
    {
        $user = $this->user($username);
        $now = new \DateTimeImmutable();
        $messageId = MessageId::generate();

        DomainEventPublisher::instance()->lock();

        $conversation = new Conversation(ConversationId::generate());
        $conversation->start($user->id(), $now->modify('-1 hour'));
        $conversation->addUserMessage(MessageId::generate(), 'Consulta anterior', '', '', 'Europe/Madrid', $now->modify('-1 hour'));
        $conversation->addPendingAssistantMessage($messageId, MessageKind::Text, $now->modify('-1 hour'));
        $conversation->completeMessage($messageId, 'Respuesta anterior', MessageKind::Text, null, new AssistantUsage('in-memory', 0, 0, 0, 0, $amount, 0), $now->modify('-1 hour'));
        $this->getConversationRepository()->add($conversation);

        DomainEventPublisher::instance()->clearPending();
    }

    #[Given('a proposed plan with id :planId in conversation :conversationId of user :username exists')]
    public function aProposedPlanOfUserExists(string $planId, string $conversationId, string $username): void
    {
        $user = $this->user($username);
        $now = new \DateTimeImmutable();
        $messageId = MessageId::generate();
        $plan = ActionPlanMother::proposed($user->id(), new ConversationId($conversationId), now: $now, id: new ActionPlanId($planId));

        DomainEventPublisher::instance()->lock();

        $conversation = new Conversation(new ConversationId($conversationId));
        $conversation->start($user->id(), $now);
        $conversation->addUserMessage(MessageId::generate(), 'Crea la tarea', '', '', 'Europe/Madrid', $now);
        $conversation->addPendingAssistantMessage($messageId, MessageKind::Text, $now);
        $conversation->completeMessage($messageId, $plan->summary(), MessageKind::Plan, $plan->id(), AssistantUsage::none(), $now);
        $this->getConversationRepository()->add($conversation);
        $this->getPlanRepository()->add($plan);

        DomainEventPublisher::instance()->clearPending();
    }

    #[When('I execute the last plan')]
    public function iExecuteTheLastPlan(): void
    {
        $this->postToPlan('execute', 'execute-plan');
    }

    #[When('I discard the last plan')]
    public function iDiscardTheLastPlan(): void
    {
        $this->postToPlan('discard', 'discard-plan');
    }

    #[When('I execute the plan :planId')]
    public function iExecuteThePlan(string $planId): void
    {
        $this->postToPlan('execute', 'execute-plan', $planId);
    }

    #[When('I discard the plan :planId')]
    public function iDiscardThePlan(string $planId): void
    {
        $this->postToPlan('discard', 'discard-plan', $planId);
    }

    #[When('I post the message :text to the assistant')]
    public function iPostTheMessageToTheAssistant(string $text): void
    {
        $this->client->request(
            'POST',
            '/es/assistant/messages',
            ['HTTP_SEC_FETCH_SITE' => 'same-origin', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
            http_build_query(['send_assistant_message' => ['text' => $text]]),
        );
    }

    #[Then('the plan :planId has status :status')]
    public function thePlanHasStatus(string $planId, string $status): void
    {
        Assert::assertSame($status, $this->getPlanRepository()->find(new ActionPlanId($planId))?->status()->value);
    }

    #[When('I open the thread of the last conversation')]
    public function iOpenTheThreadOfTheLastConversation(): void
    {
        $this->client->request('GET', sprintf('/es/assistant/conversations/%s/thread', $this->lastConversation()->id()));
    }

    #[When('I open the panel of the last conversation')]
    public function iOpenThePanelOfTheLastConversation(): void
    {
        $this->client->request('GET', sprintf('/es/assistant/panel?conversationId=%s', $this->lastConversation()->id()));
    }

    #[Given('the last plan was proposed :minutes minutes ago')]
    public function theLastPlanWasProposedMinutesAgo(int $minutes): void
    {
        $plan = $this->lastPlan();
        $property = new \ReflectionProperty(ActionPlan::class, 'proposedAt');
        $property->setValue($plan, (new \DateTimeImmutable())->modify(sprintf('-%d minutes', $minutes)));
    }

    #[Then('the plan of the last conversation has status :status')]
    public function thePlanOfTheLastConversationHasStatus(string $status): void
    {
        Assert::assertSame($status, $this->lastPlan()->status()->value);
    }

    #[Then('the last conversation has :count messages')]
    public function theLastConversationHasMessages(int $count): void
    {
        Assert::assertCount($count, $this->lastConversation()->messages());
    }

    #[Then('user :username has :count assistant conversations')]
    public function userHasAssistantConversations(string $username, int $count): void
    {
        Assert::assertSame($count, $this->getConversationRepository()->totalResultsByFilter(new ConversationFilter(userId: $this->user($username)->id(), limit: 0)));
    }

    #[When('I send the assistant message :text attaching :fileNames')]
    public function iSendTheAssistantMessageAttaching(string $text, string $fileNames): void
    {
        $files = array_map(
            fn(string $fileName): UploadedFile => new UploadedFile($this->createLocalFile(trim($fileName), 32), trim($fileName), null, null, true),
            explode(',', $fileNames),
        );

        $this->postMessageWithFiles($text, $files);
    }

    #[When('I send the assistant message :text attaching the file :fileName of :bytes bytes')]
    public function iSendTheAssistantMessageAttachingTheFileOfBytes(string $text, string $fileName, int $bytes): void
    {
        $this->postMessageWithFiles($text, [new UploadedFile($this->createLocalFile($fileName, $bytes), $fileName, null, null, true)]);
    }

    #[When('I download the first attachment of the last conversation')]
    public function iDownloadTheFirstAttachmentOfTheLastConversation(): void
    {
        $conversation = $this->lastConversation();
        $attachments = [];

        foreach ($conversation->messages() as $message) {
            foreach ($message->attachments() as $attachment) {
                $attachments[] = $attachment;
            }
        }

        Assert::assertNotEmpty($attachments, 'The last conversation has no attachments');

        $this->client->request('GET', sprintf('/es/assistant/conversations/%s/attachments/%s', $conversation->id(), $attachments[0]->id()));
    }

    #[Then('the last conversation has :count stored attachment files')]
    public function theLastConversationHasStoredAttachmentFiles(int $count): void
    {
        Assert::assertCount($count, $this->getAttachmentStorage()->pathsOf($this->lastConversation()->id()));
    }

    #[Then('the assistant has :count stored attachment files')]
    public function theAssistantHasStoredAttachmentFiles(int $count): void
    {
        $total = 0;

        foreach ($this->getConversationRepository()->findByFilter(new ConversationFilter(limit: 0)) as $conversation) {
            $total += count($this->getAttachmentStorage()->pathsOf($conversation->id()));
        }

        Assert::assertSame($count, $total);
    }

    #[Then('the last assistant request contains the document :name')]
    public function theLastAssistantRequestContainsTheDocument(string $name): void
    {
        foreach ($this->lastRequestBlocks() as $block) {
            foreach ([$block->attachment, ...$block->attachments] as $attachment) {
                if (null !== $attachment && $attachment->name === $name) {
                    return;
                }
            }
        }

        Assert::fail(sprintf('The last assistant request does not contain the document "%s"', $name));
    }

    #[Then('the last assistant request does not contain the document :name')]
    public function theLastAssistantRequestDoesNotContainTheDocument(string $name): void
    {
        foreach ($this->lastRequestBlocks() as $block) {
            foreach ([$block->attachment, ...$block->attachments] as $attachment) {
                Assert::assertNotSame($name, $attachment?->name);
            }
        }
    }

    #[Then('the last assistant request contains the text :text')]
    public function theLastAssistantRequestContainsTheText(string $text): void
    {
        foreach ($this->lastRequestBlocks() as $block) {
            if (str_contains($block->text, $text) || str_contains((string) json_encode($block->text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $text)) {
                return;
            }
        }

        Assert::fail(sprintf('The last assistant request does not contain the text "%s"', $text));
    }

    #[Then('the last assistant request does not contain the text :text')]
    public function theLastAssistantRequestDoesNotContainTheText(string $text): void
    {
        foreach ($this->lastRequestBlocks() as $block) {
            Assert::assertStringNotContainsString($text, $block->text);
        }
    }

    #[When('I stop the assistant conversation')]
    public function iStopTheAssistantConversation(): void
    {
        $this->postToConversation('stop', 'stop-assistant');
    }

    #[When('I rename the assistant conversation to :title')]
    public function iRenameTheAssistantConversationTo(string $title): void
    {
        $this->postToConversation('rename', 'rename-conversation', ['title' => $title]);
    }

    #[When('I remove the assistant conversation')]
    public function iRemoveTheAssistantConversation(): void
    {
        $this->postToConversation('remove', 'remove-conversation');
    }

    #[Then('the last conversation is titled :title')]
    public function theLastConversationIsTitled(string $title): void
    {
        Assert::assertSame($title, $this->lastConversation()->title());
    }

    #[Then('the last message of the last conversation is failed with :error')]
    public function theLastMessageOfTheLastConversationIsFailedWith(string $error): void
    {
        $messages = $this->lastConversation()->messages();
        $last = $messages[count($messages) - 1];

        Assert::assertTrue($last->isFailed(), 'The last message is not failed');
        Assert::assertSame($error, $last->error());
    }

    #[Given('the last conversation has a pending answer')]
    public function theLastConversationHasAPendingAnswer(): void
    {
        $conversation = $this->lastConversation();

        DomainEventPublisher::instance()->lock();

        $conversation->addPendingAssistantMessage(MessageId::generate(), MessageKind::Text, new \DateTimeImmutable());
        $this->getConversationRepository()->add($conversation);

        DomainEventPublisher::instance()->clearPending();
    }

    #[Then('the assistant message of the last conversation is not pending')]
    public function theAssistantMessageOfTheLastConversationIsNotPending(): void
    {
        Assert::assertNull($this->lastConversation()->pendingMessage());
    }

    /** @param list<UploadedFile> $files */
    private function postMessageWithFiles(string $text, array $files): void
    {
        $this->client->request('GET', '/es/assistant/panel');

        $values = $this->client->getCrawler()->selectButton('Enviar')->form()->getPhpValues();
        $values['send_assistant_message']['text'] = $text;

        $this->client->requestWithFiles(
            'POST',
            '/es/assistant/messages',
            $values,
            [] === $files ? [] : ['send_assistant_message' => ['attachments' => $files]],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );
    }

    /** @param array<string, string> $parameters */
    private function postToConversation(string $action, string $tokenId, array $parameters = []): void
    {
        $this->client->request('GET', '/es/assistant/panel');

        $token = $this->client->getContainer()->get(CsrfTokenManagerInterface::class)->getToken($tokenId)->getValue();

        $this->client->requestWithFiles(
            'POST',
            sprintf('/es/assistant/conversations/%s/%s', $this->lastConversation()->id(), $action),
            [...$parameters, '_token' => $token],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );
    }

    /** @return list<AiAgentBlock> */
    private function lastRequestBlocks(): array
    {
        $request = $this->getAgentClient()->lastRequest();

        Assert::assertNotNull($request, 'The assistant has not made any request');

        $blocks = [];

        foreach ($request->messages as $message) {
            foreach ($message->blocks as $block) {
                $blocks[] = $block;
            }
        }

        return $blocks;
    }

    private function createLocalFile(string $fileName, int $bytes): string
    {
        $path = sprintf('%s/%s', sys_get_temp_dir(), uniqid('behat-assistant-', true));

        $contents = match (pathinfo($fileName, PATHINFO_EXTENSION)) {
            'pdf' => "%PDF-1.4\n%behat\n",
            'png' => (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
            'zip' => "PK\x03\x04\x14\x00\x00\x00\x00\x00",
            default => sprintf('contents of %s', $fileName),
        };

        file_put_contents($path, strlen($contents) >= $bytes ? $contents : str_pad($contents, $bytes, ' '));

        return $path;
    }

    private function getAttachmentStorage(): InMemoryConversationAttachmentStorage
    {
        $storage = $this->client->getContainer()->get(InMemoryConversationAttachmentStorage::class);

        Assert::assertInstanceOf(InMemoryConversationAttachmentStorage::class, $storage);

        return $storage;
    }

    private function postToPlan(string $action, string $tokenId, ?string $planId = null): void
    {
        $token = $this->client->getContainer()->get(CsrfTokenManagerInterface::class)->getToken($tokenId)->getValue();

        $this->client->request(
            'POST',
            sprintf('/es/assistant/plans/%s/%s?_token=%s', $planId ?? $this->lastPlan()->id(), $action, urlencode($token)),
            ['HTTP_SEC_FETCH_SITE' => 'same-origin', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );
    }

    private function lastConversation(): Conversation
    {
        $conversations = $this->getConversationRepository()->findByFilter(new ConversationFilter(limit: 1));

        Assert::assertNotEmpty($conversations, 'There is no assistant conversation');

        return $conversations[0];
    }

    private function lastPlan(): ActionPlan
    {
        $plans = $this->getPlanRepository()->findByFilter(new ActionPlanFilter(conversationId: $this->lastConversation()->id(), limit: 1));

        Assert::assertNotEmpty($plans, 'The last conversation has no plan');

        return $plans[0];
    }

    private function user(string $username): User
    {
        $user = $this->client->getContainer()->get(UserRepository::class)->findByUsername($username);

        Assert::assertInstanceOf(User::class, $user, sprintf('User "%s" not found', $username));

        return $user;
    }

    private function getConversationRepository(): InMemoryConversationRepository
    {
        $repository = $this->client->getContainer()->get(ConversationRepository::class);

        Assert::assertInstanceOf(InMemoryConversationRepository::class, $repository);

        return $repository;
    }

    private function getPlanRepository(): InMemoryActionPlanRepository
    {
        $repository = $this->client->getContainer()->get(ActionPlanRepository::class);

        Assert::assertInstanceOf(InMemoryActionPlanRepository::class, $repository);

        return $repository;
    }

    private function getAgentClient(): InMemoryAiAgentClient
    {
        return $this->client->getContainer()->get(InMemoryAiAgentClient::class);
    }
}
