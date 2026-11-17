<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\Assistant;

use App\Security\Application\User\Assistant\ViewUserTool;
use App\Security\Application\User\View\ViewUserService;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class ViewUserToolTest extends TestCase
{
    private InMemoryUserRepository $users;

    private ViewUserTool $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->users = UserRepositoryMother::inMemory();
        $authorizer = UserAuthorizerMother::create();
        $this->sut = new ViewUserTool(new ViewUserService($this->users, $authorizer), $authorizer);
    }

    public function testIsAReadToolAvailableOnlyToAdmins(): void
    {
        $this->assertSame('view_user', $this->sut->name());
        $this->assertSame(ToolMode::Read, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo(UserMother::adminUser()));
        $this->assertFalse($this->sut->isAvailableTo(UserMother::registeredUser()));
    }

    public function testReturnsTheUserDetail(): void
    {
        $ana = UserMother::fromUsername('ana@acme.test');
        $this->users->add($ana);

        $result = $this->sut->execute($this->arguments(['userId' => (string) $ana->id()]), UserMother::adminUser(), $this->context());

        $this->assertSame((string) $ana->id(), $result->data['id']);
        $this->assertSame('route:admin_users_edit?userId=' . $ana->id(), $result->data['href']);
        $this->assertSame('ana@acme.test', $result->data['username']);
        $this->assertSame(['ROLE_USER'], $result->data['roles']);
        $this->assertNull($result->data['account']);
        $this->assertArrayHasKey('preferredLocale', $result->data);
        $this->assertSame(['userId' => (string) $ana->id()], $result->references);
        $this->assertSame('admin_users_edit', $result->link?->route);
        $this->assertSame(['userId'], $result->link->parameters);
    }

    public function testFailsWhenTheUserDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->sut->execute($this->arguments(['userId' => (string) UserId::generate()]), UserMother::adminUser(), $this->context());
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
