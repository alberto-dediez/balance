<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\ApiToken\Assistant;

use App\Security\Application\ApiToken\ApiTokenAuthorizer;
use App\Security\Application\ApiToken\Assistant\ListApiTokensTool;
use App\Security\Application\ApiToken\List\ListApiTokensService;
use App\Security\Domain\Model\ApiToken\ApiTokenSecret;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryApiTokenRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolResult;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenMother;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class ListApiTokensToolTest extends TestCase
{
    private InMemoryApiTokenRepository $apiTokens;

    private InMemoryUserRepository $users;

    private User $owner;

    private ListApiTokensTool $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->apiTokens = ApiTokenRepositoryMother::inMemory();
        $this->users = UserRepositoryMother::inMemory();
        $this->owner = UserMother::fromUsername('ana@acme.test');
        $this->users->add($this->owner);

        $this->sut = new ListApiTokensTool(
            new ListApiTokensService($this->apiTokens, new ApiTokenAuthorizer($this->users), $this->users),
            UserAuthorizerMother::create(),
        );
    }

    public function testIsAReadToolAvailableOnlyToAdmins(): void
    {
        $this->assertSame('list_api_tokens', $this->sut->name());
        $this->assertSame(ToolMode::Read, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo(UserMother::adminUser()));
        $this->assertFalse($this->sut->isAvailableTo(UserMother::registeredUser()));
    }

    public function testTheUserIsRequired(): void
    {
        $this->expectException(InvalidToolArgumentsException::class);

        $this->arguments([]);
    }

    public function testListsTheTokensOfTheUserWithoutTheSecret(): void
    {
        $secret = ApiTokenSecret::generate();
        $this->apiTokens->add(ApiTokenMother::withSecret($secret, $this->owner->id(), name: 'CI'));
        $this->apiTokens->add(ApiTokenMother::revoked($this->owner->id(), name: 'Old'));
        $this->apiTokens->add(ApiTokenMother::active(UserMother::registeredUser()->id(), name: 'Other'));

        $all = $this->sut->execute($this->arguments(['userId' => (string) $this->owner->id()]), UserMother::adminUser(), $this->context());
        $active = $this->sut->execute($this->arguments(['userId' => (string) $this->owner->id(), 'status' => 'Active']), UserMother::adminUser(), $this->context());

        $this->assertSame(2, $all->data['total']);
        $this->assertSame(1, $active->data['total']);
        $this->assertSame('CI', $active->data['apiTokens'][0]['name']);
        $this->assertSame(['name', 'status', 'registeredAt', 'expiresAt', 'lastUsedAt', 'revokedAt'], array_keys($active->data['apiTokens'][0]));
        $this->assertSame('route:admin_users_api_tokens?userId=' . $this->owner->id(), $all->link?->hrefFrom($all->references));

        $serialized = (string) json_encode([$all->summary, $all->data, $all->references]);

        $this->assertStringNotContainsString($secret->plainValue(), $serialized);
        $this->assertStringNotContainsString($secret->hash(), $serialized);
        $this->assertStringNotContainsString($secret->prefix(), $serialized);
    }

    public function testReturnsAtMostTheMaximumNumberOfItems(): void
    {
        for ($i = 0; $i < ToolResult::ITEMS_MAX_COUNT + 5; ++$i) {
            $this->apiTokens->add(ApiTokenMother::active($this->owner->id(), name: sprintf('token %d', $i)));
        }

        $result = $this->sut->execute($this->arguments(['userId' => (string) $this->owner->id(), 'limit' => 50]), UserMother::adminUser(), $this->context());

        $this->assertCount(ToolResult::ITEMS_MAX_COUNT, $result->data['apiTokens']);
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
