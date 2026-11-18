<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Conversation;

use App\Assistant\Application\Conversation\List\ListConversationsRequest;
use App\Assistant\Application\Conversation\List\ListConversationsService;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\Assistant\Domain\Model\Conversation\ConversationMother;
use App\Tests\Unit\Assistant\Domain\Model\Conversation\ConversationRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListConversationsServiceTest extends TestCase
{
    public function testListsOnlyTheConversationsOfTheUserMostRecentFirst(): void
    {
        $admin = UserMother::adminUser();
        $other = UserMother::superAdminUser();
        $repository = ConversationRepositoryMother::inMemory();
        $old = ConversationMother::withQuestion($admin->id(), 'Antigua', new \DateTimeImmutable('2026-11-01 10:00:00'));
        $recent = ConversationMother::withQuestion($admin->id(), 'Reciente', new \DateTimeImmutable('2026-11-10 10:00:00'));
        $repository->add($old);
        $repository->add($recent);
        $repository->add(ConversationMother::withQuestion($other->id(), 'Ajena'));

        $request = new ListConversationsRequest(userId: $other->id());
        $response = (new ListConversationsService($repository, ConversationAuthorizerMother::create()))($request, $admin);

        $this->assertSame(['Reciente', 'Antigua'], array_map(static fn($conversation) => $conversation->title(), $response->conversations));
        $this->assertSame(2, $response->totalResults);
    }

    public function testOwnersCannotList(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        (new ListConversationsService(ConversationRepositoryMother::inMemory(), ConversationAuthorizerMother::create()))(new ListConversationsRequest(), UserMother::ownerUser());
    }
}
