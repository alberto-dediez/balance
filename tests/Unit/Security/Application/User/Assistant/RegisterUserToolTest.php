<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\Assistant;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\Assistant\RegisterUserTool;
use App\Security\Application\User\Register\RegisterUserService;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Notifications\Application\Notification\Deliver\SpyEmailSender;
use App\Tests\Unit\Security\Application\User\Register\SpyPasswordHasher;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class RegisterUserToolTest extends TestCase
{
    private InMemoryUserRepository $users;

    private InMemoryAccountRepository $accounts;

    private SpyEmailSender $emailSender;

    private SpyPasswordHasher $passwordHasher;

    private RegisterUserTool $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->users = UserRepositoryMother::inMemory();
        $this->accounts = AccountRepositoryMother::inMemory();
        $this->emailSender = new SpyEmailSender();
        $this->passwordHasher = new SpyPasswordHasher();
        $authorizer = UserAuthorizerMother::create();
        $this->sut = new RegisterUserTool(
            new RegisterUserService($this->users, $this->passwordHasher, $authorizer),
            $this->users,
            $this->accounts,
            $authorizer,
        );
    }

    public function testIsAWriteToolAvailableOnlyToAdmins(): void
    {
        $this->assertSame('register_user', $this->sut->name());
        $this->assertSame(ToolMode::Write, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo(UserMother::adminUser()));
        $this->assertFalse($this->sut->isAvailableTo(UserMother::registeredUser()));

        $this->expectException(UnauthorizedActionException::class);

        $this->sut->execute($this->arguments(['username' => 'ana@acme.test']), UserMother::registeredUser(), $this->context());
    }

    public function testDoesNotDeclareAPasswordParameter(): void
    {
        $names = array_map(static fn(ToolParameter $parameter): string => $parameter->name, $this->sut->parameters());

        $this->assertSame(['username', 'accountId'], $names);
    }

    public function testThePreviewRejectsADuplicatedUsername(): void
    {
        $this->users->add(UserMother::fromUsername('ana@acme.test'));

        $this->expectException(InvalidToolArgumentsException::class);

        $this->sut->preview($this->arguments(['username' => 'ana@acme.test']), UserMother::adminUser(), $this->context());
    }

    public function testThePreviewRejectsAnInvalidUsername(): void
    {
        $this->expectException(InvalidToolArgumentsException::class);

        $this->sut->preview($this->arguments(['username' => '<ana>@acme.test']), UserMother::adminUser(), $this->context());
    }

    public function testThePreviewShowsTheAccountName(): void
    {
        $account = AccountMother::fromName('Acme');
        $this->accounts->add($account);

        $preview = $this->sut->preview($this->arguments(['username' => 'ana@acme.test', 'accountId' => (string) $account->id()]), UserMother::adminUser(), $this->context());

        $this->assertSame(['accountId' => 'Acme'], $preview->displays);
    }

    public function testThePreviewRejectsAnUnknownAccount(): void
    {
        $this->expectException(InvalidToolArgumentsException::class);

        $this->sut->preview($this->arguments(['username' => 'ana@acme.test', 'accountId' => (string) AccountId::generate()]), UserMother::adminUser(), $this->context());
    }

    public function testThePreviewToleratesADeferredAccount(): void
    {
        $preview = $this->sut->preview($this->arguments(['username' => 'ana@acme.test']), UserMother::adminUser(), $this->context());

        $this->assertSame([], $preview->displays);
    }

    public function testRegistersTheUserWithARandomPasswordThatIsNeverReturned(): void
    {
        $account = AccountMother::fromName('Acme');
        $this->accounts->add($account);

        $result = $this->sut->execute($this->arguments(['username' => 'ana@acme.test', 'accountId' => (string) $account->id()]), UserMother::adminUser(), $this->context());

        $user = $this->users->find(new UserId($result->references['userId']));

        $this->assertNotNull($user);
        $this->assertSame('ana@acme.test', $user->username());
        $this->assertNotNull($user->accountId());
        $this->assertTrue($account->id()->equals($user->accountId()));
        $this->assertCount(1, $this->passwordHasher->passwords());
        $password = $this->passwordHasher->passwords()[0];
        $this->assertNotSame('', $password);
        $this->assertSame('admin_users_edit', $result->link?->route);
        $this->assertSame(['userId'], $result->link->parameters);
        $this->assertResultDoesNotContain($password, $result);
        $this->assertResultDoesNotContain($user->password(), $result);
        $this->assertSame([], $this->emailSender->sent());
    }

    public function testEveryExecutionGeneratesADifferentPassword(): void
    {
        $first = $this->sut->execute($this->arguments(['username' => 'ana@acme.test']), UserMother::adminUser(), $this->context());
        $second = $this->sut->execute($this->arguments(['username' => 'luis@acme.test']), UserMother::adminUser(), $this->context());

        $passwords = $this->passwordHasher->passwords();

        $this->assertCount(2, $passwords);
        $this->assertNotSame($passwords[0], $passwords[1]);
        $this->assertResultDoesNotContain($passwords[0], $first);
        $this->assertResultDoesNotContain($passwords[1], $second);
    }

    public function testTheExecutionRejectsAUsernameRegisteredAfterThePreview(): void
    {
        $arguments = $this->arguments(['username' => 'ana@acme.test']);
        $this->sut->preview($arguments, UserMother::adminUser(), $this->context());
        $this->users->add(UserMother::fromUsername('ana@acme.test'));

        $this->expectException(InvalidToolArgumentsException::class);
        $this->expectExceptionMessage('El usuario «ana@acme.test» ya existe.');

        $this->sut->execute($arguments, UserMother::adminUser(), $this->context());
    }

    private function assertResultDoesNotContain(string $secret, ToolResult $result): void
    {
        $serialized = (string) json_encode([$result->summary, $result->data, $result->references, $result->link]);

        $this->assertStringNotContainsString($secret, $serialized);
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
