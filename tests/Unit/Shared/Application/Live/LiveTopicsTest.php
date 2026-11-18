<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Live;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Live\LiveTopics;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentRoles\StubCurrentRolesProvider;
use PHPUnit\Framework\TestCase;

class LiveTopicsTest extends TestCase
{
    public function testAnAdminSubscribesToTheAdminTopicAndItsOwn(): void
    {
        $user = UserMother::adminUser();

        $this->assertSame(['/live/admin', '/live/users/' . $user->id()], $this->createSUT()->for($user));
    }

    public function testASuperAdminSubscribesToTheAdminTopicAndItsOwn(): void
    {
        $user = UserMother::superAdminUser();

        $this->assertSame(['/live/admin', '/live/users/' . $user->id()], $this->createSUT()->for($user));
    }

    /**
     * @dataProvider nonAdminUsers
     */
    public function testNonAdminsSubscribeOnlyToTheirOwnTopic(User $user): void
    {
        $this->assertSame(['/live/users/' . $user->id()], $this->createSUT()->for($user));
    }

    /**
     * @return iterable<string, array{User}>
     */
    public static function nonAdminUsers(): iterable
    {
        yield 'owner' => [UserMother::ownerUser()];
        yield 'user' => [UserMother::registeredUser()];
    }

    public function testAnAdminImpersonatingAnAccountSubscribesOnlyToItsOwnTopic(): void
    {
        $user = UserMother::adminUser();

        $this->assertSame(['/live/users/' . $user->id()], $this->createSUT([Role::Owner, Role::User])->for($user));
    }

    /**
     * @param Role[]|null $currentRoles
     */
    private function createSUT(?array $currentRoles = null): LiveTopics
    {
        return new LiveTopics(new StubCurrentRolesProvider($currentRoles));
    }
}
