<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Conversation;

use App\Assistant\Application\AssistantSettings;
use App\Assistant\Application\Conversation\Answer\AnswerMessageRequest;
use App\Assistant\Application\Conversation\Answer\AnswerMessageService;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanFilter;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanStatus;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Assistant\Domain\Model\Conversation\MessageKind;
use App\Assistant\Domain\Model\Conversation\MessageStatus;
use App\Assistant\Infrastructure\Persistence\InMemory\Repository\InMemoryConversationRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Ai\Agent\AiAgentBlock;
use App\Shared\Application\Ai\Agent\AiAgentMessage;
use App\Shared\Application\Ai\Agent\AiAgentRole;
use App\Shared\Application\Ai\Agent\AiAgentStep;
use App\Shared\Application\Ai\Agent\AiStopReason;
use App\Shared\Application\Ai\Agent\AiToolCall;
use App\Shared\Application\Ai\AiAttachment;
use App\Shared\Application\Ai\AiUsage;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;
use App\Tests\Unit\Assistant\Application\AssistantServiceFixtures;
use App\Tests\Unit\Assistant\Application\Engine\FakeAssistantTool;
use App\Tests\Unit\Assistant\Domain\Model\Conversation\ConversationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class AnswerMessageServiceTest extends TestCase
{
    use AssistantServiceFixtures;

    private const string PROJECT_ID = '0190f3a4-1b2c-7d3e-8f40-000000000001';

    private User $admin;

    private MessageId $messageId;

    private Conversation $conversation;

    private FakeAssistantTool $listTasks;

    private FakeAssistantTool $registerTask;

    protected function setUp(): void
    {
        $this->setUpAssistant();

        $this->listTasks = FakeAssistantTool::read('list_tasks', ToolParameter::string('title', 'Título', ''))->willReturn(new ToolResult('2 tareas encontradas', ['total' => 2]));
        $this->registerTask = FakeAssistantTool::write('register_task', ToolParameter::id('projectId', 'Proyecto', '')->required(), ToolParameter::string('title', 'Título', '')->required());
        $this->tools = [$this->listTasks, $this->registerTask];

        $this->admin = UserMother::adminUser();
        $this->messageId = MessageId::generate();
        $this->conversation = ConversationMother::waitingForAnswer($this->admin->id(), $this->messageId);
        $this->conversations->add($this->conversation);
    }

    public function testAnswersDirectly(): void
    {
        $this->client->enqueueText('Hola, ¿en qué te ayudo?');

        $this->answer();

        $message = $this->conversation->messageOf($this->messageId);
        $this->assertSame(MessageStatus::Completed, $message?->status());
        $this->assertSame('Hola, ¿en qué te ayudo?', $message->text());
        $this->assertSame('0.000200', $message->usage()->costUsd());
        $this->assertSame([[$this->admin->id(), 'assistant']], $this->liveUpdates->touchedForUsers);

        $request = $this->client->lastRequest();
        $this->assertNotNull($request);
        $this->assertSame(['list_tasks', 'propose_plan'], array_map(static fn($tool) => $tool->name, $request->tools));
        $this->assertStringContainsString('register_task', $request->system);
    }

    public function testReadsWithAToolAndAnswers(): void
    {
        $this->client->enqueueToolCalls(new AiToolCall('t1', 'list_tasks', ['title' => 'DNS']));
        $this->client->enqueueText('Hay 2 tareas.');

        $this->answer();

        $message = $this->conversation->messageOf($this->messageId);
        $this->assertSame('Hay 2 tareas.', $message?->text());
        $this->assertCount(1, $message->toolCalls());
        $this->assertSame('2 tareas encontradas', $message->toolCalls()[0]->summary());
        $this->assertSame(['title' => ['DNS']], $message->toolCalls()[0]->argumentValues());
        $this->assertCount(1, $this->listTasks->executions);

        $results = $this->lastMessageOfRequest(1);
        $this->assertSame('{"total":2}', $results->blocks[0]->text);
        $this->assertSame('0.000400', $message->usage()->costUsd());
    }

    public function testAWriteToolCalledByTheModelIsNotExecuted(): void
    {
        $this->client->enqueueToolCalls(new AiToolCall('t1', 'register_task', ['projectId' => self::PROJECT_ID, 'title' => 'Migrar DNS']));
        $this->client->enqueueText('No puedo hacerlo directamente.');

        $this->answer();

        $this->assertSame([], $this->registerTask->executions);
        $message = $this->conversation->messageOf($this->messageId);
        $this->assertTrue($message?->toolCalls()[0]->failed());

        $results = $this->lastMessageOfRequest(1);
        $this->assertTrue($results->blocks[0]->isError);
    }

    public function testAValidPlanCompletesTheMessageAsAPlan(): void
    {
        $this->client->enqueueToolCalls($this->proposePlan(['projectId' => self::PROJECT_ID, 'title' => 'Migrar DNS']));

        $this->answer();

        $message = $this->conversation->messageOf($this->messageId);
        $this->assertSame(MessageKind::Plan, $message?->kind());
        $this->assertSame('Crear la tarea Migrar DNS', $message->text());
        $planId = $message->planId();
        $this->assertInstanceOf(ActionPlanId::class, $planId);

        $plan = $this->plans->find($planId);
        $this->assertSame(ActionPlanStatus::Proposed, $plan?->status());
        $this->assertSame(['projectId' => [self::PROJECT_ID], 'title' => ['Migrar DNS']], $plan->steps()[0]->arguments());
        $this->assertSame([], $this->registerTask->executions);
        $this->assertCount(1, $this->client->requests());
    }

    public function testAnInvalidPlanIsReturnedToTheModelToCorrectIt(): void
    {
        $this->client->enqueueToolCalls($this->proposePlan(['projectId' => self::PROJECT_ID]));
        $this->client->enqueueToolCalls($this->proposePlan(['projectId' => self::PROJECT_ID, 'title' => 'Migrar DNS']));

        $this->answer();

        $results = $this->lastMessageOfRequest(1);
        $this->assertTrue($results->blocks[0]->isError);
        $this->assertStringContainsString('Paso 1: «title»: es obligatorio.', $results->blocks[0]->text);
        $this->assertSame(MessageKind::Plan, $this->conversation->messageOf($this->messageId)?->kind());
        $this->assertCount(1, $this->plans->findByFilter(new ActionPlanFilter(limit: 0)));
    }

    public function testFailsWhenTheRoundsRunOut(): void
    {
        $this->setUpAssistant(new AssistantSettings(maxToolRounds: 2));
        $this->tools = [$this->listTasks];
        $this->conversations->add($this->conversation);
        $this->client->enqueueToolCalls(new AiToolCall('t1', 'list_tasks', []));
        $this->client->enqueueToolCalls(new AiToolCall('t2', 'list_tasks', []));

        $this->answer();

        $message = $this->conversation->messageOf($this->messageId);
        $this->assertSame(MessageStatus::Failed, $message?->status());
        $this->assertSame('La consulta necesita demasiados pasos; concreta un poco más la petición', $message->error());
    }

    public function testAProviderFailureFailsTheMessageWithoutThrowing(): void
    {
        $this->client->fail('HTTP 529 overloaded');

        $this->answer();

        $message = $this->conversation->messageOf($this->messageId);
        $this->assertSame(MessageStatus::Failed, $message?->status());
        $this->assertSame('HTTP 529 overloaded', $message->error());
        $this->assertCount(1, $this->liveUpdates->touchedForUsers);
    }

    public function testAnEmptyAnswerFailsTheMessage(): void
    {
        $this->answer();

        $this->assertSame('El asistente no ha devuelto respuesta', $this->conversation->messageOf($this->messageId)?->error());
    }

    public function testARedeliveryDoesNothing(): void
    {
        $this->client->enqueueText('Primera');
        $this->answer();
        $this->client->enqueueText('Segunda');

        $this->answer();

        $this->assertSame('Primera', $this->conversation->messageOf($this->messageId)?->text());
        $this->assertCount(1, $this->client->requests());
    }

    public function testAnUnexpectedExceptionShowsAGenericErrorWithoutItsDetails(): void
    {
        $this->client->enqueueUsing(static function (): never {
            throw new \RuntimeException('SQLSTATE[HY000]: General error at db.internal:3306');
        });

        $this->answer();

        $message = $this->conversation->messageOf($this->messageId);
        $this->assertSame(MessageStatus::Failed, $message?->status());
        $this->assertSame('Error inesperado', $message->error());
    }

    public function testAnAnswerCutByTheTokenLimitFailsTheMessage(): void
    {
        $this->client->enqueueStep(new AiAgentStep(new AiAgentMessage(AiAgentRole::Assistant, [AiAgentBlock::text('Hay muchas')]), AiUsage::none(), AiStopReason::MaxTokens));

        $this->answer();

        $this->assertSame('La respuesta es demasiado larga; concreta un poco más la petición', $this->conversation->messageOf($this->messageId)?->error());
    }

    public function testARefusalFailsTheMessage(): void
    {
        $this->client->enqueueStep(new AiAgentStep(new AiAgentMessage(AiAgentRole::Assistant, [AiAgentBlock::text('')]), AiUsage::none(), AiStopReason::Refusal));

        $this->answer();

        $this->assertSame('El asistente no puede responder a esta petición', $this->conversation->messageOf($this->messageId)?->error());
    }

    public function testAMessageInterruptedWhileAnsweringIsNotOverwritten(): void
    {
        $conversations = new class ([(string) $this->conversation->id() => $this->conversation]) extends InMemoryConversationRepository {
            public bool $interrupted = false;

            public int $saves = 0;

            public function add(Conversation $conversation): void
            {
                ++$this->saves;
                parent::add($conversation);
            }

            public function messageStatusOf(MessageId $id): ?MessageStatus
            {
                return $this->interrupted ? MessageStatus::Failed : parent::messageStatusOf($id);
            }
        };
        $this->conversations = $conversations;
        $this->listTasks->onExecute(static function () use ($conversations): void {
            $conversations->interrupted = true;
        });
        $this->client->enqueueToolCalls(new AiToolCall('t1', 'list_tasks', ['title' => 'DNS']));
        $this->client->enqueueText('Hay 2 tareas.');

        $this->answer();

        $this->assertCount(1, $this->client->requests());
        $this->assertSame(0, $conversations->saves);
        $this->assertTrue($this->conversation->messageOf($this->messageId)?->isPending());
    }

    public function testOverlongToolAndArgumentNamesAreCutToTheColumnLength(): void
    {
        $this->client->enqueueToolCalls(new AiToolCall('t1', str_repeat('x', 100), [str_repeat('a', 100) => 'valor']));
        $this->client->enqueueText('No existe.');

        $this->answer();

        $call = $this->conversation->messageOf($this->messageId)?->toolCalls()[0];
        $this->assertNotNull($call);
        $this->assertSame(64, mb_strlen($call->tool()));
        $this->assertSame(64, mb_strlen($call->arguments()[0]->name()));
    }

    public function testAToolWithAttachmentsReturnsThemInTheToolResult(): void
    {
        $attachment = new AiAttachment('informe.pdf', 'application/pdf', 'PDFDATA');
        $this->tools = [FakeAssistantTool::read('read_media')->willReturn(new ToolResult('Fichero leído', ['attached' => true], attachments: [$attachment]))];
        $this->client->enqueueToolCalls(new AiToolCall('t1', 'read_media', []));
        $this->client->enqueueText('Resumen del informe.');

        $this->answer();

        $result = $this->lastMessageOfRequest(1)->blocks[0];
        $this->assertTrue($result->isToolResult());
        $this->assertSame([$attachment], $result->attachments);
    }

    public function testTheToolCallKeepsTheResultSentToTheModel(): void
    {
        $this->client->enqueueToolCalls(new AiToolCall('t1', 'list_tasks', ['title' => 'DNS']));
        $this->client->enqueueText('Hay 2 tareas.');

        $this->answer();

        $call = $this->conversation->messageOf($this->messageId)?->toolCalls()[0];
        $this->assertSame('{"total":2}', $call?->result());
    }

    public function testTheResultSentToTheModelAndKeptInTheToolCallIncludesTheLinkReference(): void
    {
        $this->tools = [FakeAssistantTool::read('view_account')->willReturn(new ToolResult(
            'Cuenta Acme',
            ['name' => 'Acme'],
            ['accountId' => self::PROJECT_ID],
            new ToolLink('Ver cuenta', 'admin_accounts_edit', ['accountId']),
        ))];
        $this->client->enqueueToolCalls(new AiToolCall('t1', 'view_account', []));
        $this->client->enqueueText('[Acme](route:admin_accounts_edit?accountId=' . self::PROJECT_ID . ')');

        $this->answer();

        $expected = '{"name":"Acme","_link":{"label":"Ver cuenta","href":"route:admin_accounts_edit?accountId=' . self::PROJECT_ID . '"}}';
        $this->assertSame($expected, $this->lastMessageOfRequest(1)->blocks[0]->text);
        $this->assertSame($expected, $this->conversation->messageOf($this->messageId)?->toolCalls()[0]->result());
    }

    public function testTheToolsReceiveTheDocumentSizeLimit(): void
    {
        $this->settings = new AssistantSettings(documentMaxBytes: 1234);
        $this->client->enqueueToolCalls(new AiToolCall('t1', 'list_tasks', []));
        $this->client->enqueueText('Hecho.');

        $this->answer();

        $this->assertSame(1234, $this->listTasks->contexts[0]->documentMaxBytes);
    }

    public function testAFailedToolResultCarriesNoAttachments(): void
    {
        $this->tools = [FakeAssistantTool::read('read_media')->willReturn(new \DomainException('No se puede leer'))];
        $this->client->enqueueToolCalls(new AiToolCall('t1', 'read_media', []));
        $this->client->enqueueText('No he podido leerlo.');

        $this->answer();

        $result = $this->lastMessageOfRequest(1)->blocks[0];
        $this->assertTrue($result->isToolResult());
        $this->assertTrue($result->isError);
        $this->assertSame([], $result->attachments);
    }

    public function testADocumentThatDoesNotFitInTheRequestBudgetIsRejectedWithoutAttachingIt(): void
    {
        $this->settings = new AssistantSettings(transcriptAttachmentsMaxBytes: 10);
        $small = new AiAttachment('corto.pdf', 'application/pdf', 'PDFDATA');
        $large = new AiAttachment('largo.pdf', 'application/pdf', 'PDFDATAPDF');
        $this->tools = [FakeAssistantTool::read('read_media')->willReturn(
            new ToolResult('Fichero leído', ['attached' => true], attachments: [$small]),
            new ToolResult('Fichero leído', ['attached' => true], attachments: [$large]),
        )];
        $this->client->enqueueToolCalls(new AiToolCall('t1', 'read_media', []));
        $this->client->enqueueToolCalls(new AiToolCall('t2', 'read_media', []));
        $this->client->enqueueText('Solo he podido leer uno.');

        $this->answer();

        $first = $this->lastMessageOfRequest(1)->blocks[0];
        $this->assertSame([$small], $first->attachments);

        $second = $this->lastMessageOfRequest(2)->blocks[0];
        $this->assertTrue($second->isError);
        $this->assertSame([], $second->attachments);
        $this->assertSame(AnswerMessageService::DOCUMENT_TOO_LARGE_FOR_CONTEXT, $second->text);

        $call = $this->conversation->messageOf($this->messageId)?->toolCalls()[1];
        $this->assertTrue($call?->failed());
    }

    /** @param array<string, string> $arguments */
    private function proposePlan(array $arguments): AiToolCall
    {
        return new AiToolCall('p1', 'propose_plan', [
            'summary' => 'Crear la tarea Migrar DNS',
            'steps' => [[
                'tool' => 'register_task',
                'arguments' => array_map(static fn(string $name, string $value): array => ['name' => $name, 'value' => $value, 'fromStep' => null], array_keys($arguments), $arguments),
            ]],
        ]);
    }

    private function lastMessageOfRequest(int $index): AiAgentMessage
    {
        $messages = $this->client->requests()[$index]->messages;
        $last = $messages[count($messages) - 1] ?? null;
        $this->assertNotNull($last);

        return $last;
    }

    private function answer(): void
    {
        $this->answerService()(new AnswerMessageRequest($this->conversation->id(), $this->messageId), $this->admin);
    }
}
