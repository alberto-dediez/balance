<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Infrastructure\Symfony\Twig;

use App\Assistant\Application\Engine\Toolbox;
use App\Assistant\Domain\Model\Conversation\Message;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Assistant\Domain\Model\Conversation\ToolCallId;
use App\Assistant\Infrastructure\Symfony\Twig\AssistantSourcesExtension;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\SourceAssistantTool;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolResult;
use App\Tests\Unit\Assistant\Application\Engine\FakeAssistantTool;
use App\Tests\Unit\Assistant\Domain\Model\Conversation\ConversationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class AssistantSourcesExtensionTest extends TestCase
{
    public function testASuccessfulCallOfASourceToolIsASource(): void
    {
        $sut = $this->createSUT();

        $sources = $sut->sources($this->messageWith([['read_guide', ['path' => ['guide/doc']], false, 'Usuarios']]));

        $this->assertSame([['label' => 'Usuarios', 'url' => '/es/guide/guide/doc', 'document' => true]], $sources);
    }

    public function testTheSameDocumentReadTwiceIsListedOnce(): void
    {
        $sut = $this->createSUT();

        $sources = $sut->sources($this->messageWith([
            ['read_guide', ['path' => ['guide/doc']], false, 'Usuarios'],
            ['read_guide', ['path' => ['guide/doc']], false, 'Usuarios'],
            ['read_guide', ['path' => ['guide/other']], false, 'Cuentas'],
        ]));

        $this->assertCount(2, $sources);
        $this->assertSame('Usuarios', $sources[0]['label']);
        $this->assertSame('Cuentas', $sources[1]['label']);
    }

    public function testAFailedCallIsNotASource(): void
    {
        $sources = $this->createSUT()->sources($this->messageWith([['read_guide', ['path' => ['guide/doc']], true, 'Error']]));

        $this->assertSame([], $sources);
    }

    public function testAToolThatIsNotASourceToolIsIgnored(): void
    {
        $sources = $this->createSUT()->sources($this->messageWith([['list_things', ['path' => ['guide/doc']], false, 'Cosas']]));

        $this->assertSame([], $sources);
    }

    public function testAnUnknownToolIsIgnored(): void
    {
        $sources = $this->createSUT()->sources($this->messageWith([['no_existe', ['path' => ['guide/doc']], false, 'Nada']]));

        $this->assertSame([], $sources);
    }

    public function testACallWithoutTheArgumentIsIgnored(): void
    {
        $sources = $this->createSUT()->sources($this->messageWith([['read_guide', [], false, 'Usuarios']]));

        $this->assertSame([], $sources);
    }

    public function testAHrefThatDoesNotResolveIsIgnored(): void
    {
        $sources = $this->createSUT()->sources($this->messageWith([['read_broken', ['path' => ['x']], false, 'Roto']]));

        $this->assertSame([], $sources);
    }

    public function testASourceOfARouteThatIsNotADocumentIsNotMarkedAsDocument(): void
    {
        $sources = $this->createSUT()->sources($this->messageWith([['read_users', ['path' => ['x']], false, 'Usuarios']]));

        $this->assertFalse($sources[0]['document']);
    }

    private function createSUT(): AssistantSourcesExtension
    {
        $toolbox = new Toolbox([
            $this->sourceTool('read_guide', 'route:guide_article?path='),
            $this->sourceTool('read_broken', 'route:no_existe'),
            $this->sourceTool('read_users', 'route:admin_users'),
            FakeAssistantTool::read('list_things'),
        ]);

        return new AssistantSourcesExtension($toolbox, AssistantLinkResolverTest::createSUT());
    }

    /**
     * @param list<array{0: string, 1: array<string, list<string>>, 2: bool, 3: string}> $calls
     */
    private function messageWith(array $calls): Message
    {
        $user = UserMother::adminUser();
        $messageId = MessageId::generate();
        $conversation = ConversationMother::waitingForAnswer($user->id(), $messageId);

        foreach ($calls as $call) {
            [$tool, $arguments, $failed, $summary] = $call;
            $conversation->recordToolCall($messageId, ToolCallId::generate(), $tool, $arguments, $summary, $failed, 1);
        }

        $message = $conversation->messageOf($messageId);

        $this->assertInstanceOf(Message::class, $message);

        return $message;
    }

    private function sourceTool(string $name, string $href): SourceAssistantTool
    {
        return new readonly class ($name, $href) implements SourceAssistantTool {
            public function __construct(private string $name, private string $href) {}

            public function name(): string
            {
                return $this->name;
            }

            public function label(): string
            {
                return $this->name;
            }

            public function description(): string
            {
                return $this->name;
            }

            public function mode(): ToolMode
            {
                return ToolMode::Read;
            }

            public function parameters(): array
            {
                return [];
            }

            public function isAvailableTo(User $user): bool
            {
                return true;
            }

            public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
            {
                return new ToolResult('');
            }

            public function sourceHref(array $arguments): ?string
            {
                if (!isset($arguments['path'])) {
                    return null;
                }

                return str_ends_with($this->href, 'path=') ? $this->href . rawurlencode($arguments['path'][0]) : $this->href;
            }
        };
    }
}
