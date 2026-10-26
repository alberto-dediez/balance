<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\User;

use App\Security\Domain\Model\User\InvalidUsernameException;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRegistered;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Event\DomainEventPublisher;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRegisterSetsUsername(): void
    {
        $user = new User(UserId::generate());

        $user->register('an@email.com', 'hash');

        $events = array_values($user->recordedEvents());

        $this->assertSame('an@email.com', $user->username());
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserRegistered::class, $events[0]);
    }

    public function testRegisterRejectsHtmlUsername(): void
    {
        $user = new User(UserId::generate());

        try {
            $user->register('<b>pepe</b>', 'hash');
            $this->fail('InvalidUsernameException was expected');
        } catch (InvalidUsernameException) {
        }

        $this->assertCount(0, $user->recordedEvents());
        $this->assertSame('', $user->username());
    }

    public function testLegacyUsernameWithHtmlCanBeConstructed(): void
    {
        $user = UserMother::fromUsername('<b>legacy</b>');

        $this->assertSame('<b>legacy</b>', $user->username());
    }

    public function testLegacyUserRegisteredWithHtmlCanBeRebuilt(): void
    {
        $id = UserId::generate();

        $event = UserRegistered::rebuildFromPayload(DomainEventId::generate(), new \DateTimeImmutable(), [
            'userId' => (string) $id,
            'username' => '<b>legacy</b>',
            'password' => 'hash',
            'accountId' => '',
        ]);

        $this->assertSame('<b>legacy</b>', $event->username());

        $user = new User($id);
        $user->applyUserRegistered($event);

        $this->assertSame('<b>legacy</b>', $user->username());
    }
}
