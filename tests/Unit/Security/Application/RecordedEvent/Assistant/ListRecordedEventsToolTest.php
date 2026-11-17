<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\RecordedEvent\Assistant;

use App\Security\Application\RecordedEvent\Assistant\ListRecordedEventsTool;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsService;
use App\Security\Application\User\UserFinder;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRegistered;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryRecordedEventRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolResult;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\RecordedEvent\RecordedEventAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\RecordedEvent\RecordedEventMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class ListRecordedEventsToolTest extends TestCase
{
    private InMemoryRecordedEventRepository $events;

    private InMemoryUserRepository $users;

    private ListRecordedEventsTool $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->events = new InMemoryRecordedEventRepository();
        $this->users = UserRepositoryMother::inMemory();
        $authorizer = RecordedEventAuthorizerMother::create();
        $this->sut = new ListRecordedEventsTool(new ListRecordedEventsService($this->events, $authorizer), $authorizer, new UserFinder($this->users));
    }

    public function testIsAReadToolAvailableOnlyToAdmins(): void
    {
        $this->assertSame('list_recorded_events', $this->sut->name());
        $this->assertSame(ToolMode::Read, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo(UserMother::adminUser()));
        $this->assertFalse($this->sut->isAvailableTo(UserMother::registeredUser()));
    }

    public function testListsTheEventsWithoutTheirContent(): void
    {
        $ana = UserMother::fromUsername('ana@acme.test');
        $this->users->add($ana);
        $accountId = AccountId::generate();
        $this->events->add(RecordedEventMother::from($ana->id(), new AccountRegistered($accountId, 'Confidential Corp', true)));

        $result = $this->sut->execute($this->arguments([]), UserMother::adminUser(), $this->context());

        $this->assertSame(1, $result->data['total']);
        $event = $result->data['events'][0];
        $this->assertSame('accountRegistered', $event['eventName']);
        $this->assertSame('security', $event['context']);
        $this->assertSame((string) $accountId, $event['aggregateRootId']);
        $this->assertSame(['id' => (string) $ana->id(), 'href' => 'route:admin_users_edit?userId=' . $ana->id(), 'username' => 'ana@acme.test'], $event['user']);
        $this->assertFalse($event['byAssistant']);
        $this->assertSame(['occurredOn', 'eventName', 'context', 'aggregateRootId', 'user', 'byAssistant'], array_keys($event));
        $this->assertStringNotContainsString('Confidential Corp', (string) json_encode($result->data));
        $this->assertSame('admin_events', $result->link?->route);
    }

    public function testFiltersTheEventsMadeByTheAssistant(): void
    {
        $this->events->add(RecordedEventMother::random());
        $this->events->add(RecordedEventMother::byAssistant('execution-1'));

        $result = $this->sut->execute($this->arguments(['byAssistant' => true]), UserMother::adminUser(), $this->context());

        $this->assertSame(1, $result->data['total']);
        $this->assertTrue($result->data['events'][0]['byAssistant']);
    }

    public function testReturnsAtMostTheMaximumNumberOfItems(): void
    {
        for ($i = 0; $i < ToolResult::ITEMS_MAX_COUNT + 5; ++$i) {
            $this->events->add(RecordedEventMother::random());
        }

        $result = $this->sut->execute($this->arguments(['limit' => 50]), UserMother::adminUser(), $this->context());

        $this->assertCount(ToolResult::ITEMS_MAX_COUNT, $result->data['events']);
    }

    /** @param array<string, mixed> $raw */
    private function arguments(array $raw): ToolArguments
    {
        return ToolArgumentsFactory::create($this->sut->parameters(), $raw, $this->context());
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-16 10:00:00'), new \DateTimeZone('Europe/Madrid'));
    }
}
