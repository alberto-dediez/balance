<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Twig;

use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\Live\LiveTopics;
use App\Shared\Infrastructure\Symfony\Twig\LiveExtension;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use App\Tests\Unit\Shared\Application\CurrentRoles\StubCurrentRolesProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

class LiveExtensionTest extends TestCase
{
    public function testDisabledReturnsEmpty(): void
    {
        $this->assertSame('', $this->createSUT(UserMother::adminUser(), false)->hubUrl());
    }

    public function testWithoutUserReturnsEmpty(): void
    {
        $this->assertSame('', $this->createSUT(null)->hubUrl());
    }

    public function testAnAdminGetsThePublicUrlWithBothTopics(): void
    {
        $user = UserMother::adminUser();

        $this->assertSame(
            'https://example.com/.well-known/mercure?topic=%2Flive%2Fadmin&topic=%2Flive%2Fusers%2F' . $user->id(),
            $this->createSUT($user)->hubUrl(),
        );
    }

    public function testAUserGetsOnlyItsOwnTopic(): void
    {
        $user = UserMother::registeredUser();

        $this->assertSame(
            'https://example.com/.well-known/mercure?topic=%2Flive%2Fusers%2F' . $user->id(),
            $this->createSUT($user)->hubUrl(),
        );
    }

    public function testRegistersTheFunction(): void
    {
        $this->assertSame('live_hub_url', $this->createSUT(null)->getFunctions()[0]->getName());
    }

    private function createSUT(?User $user, bool $enabled = true): LiveExtension
    {
        $loggedUser = new LoggedUser($this->createMock(Security::class), UserRepositoryMother::inMemory());
        $loggedUser->forceUser($user);

        $hub = new MockHub(
            'http://php/.well-known/mercure',
            new StaticTokenProvider('token'),
            static fn(Update $update): string => 'id',
            publicUrl: 'https://example.com/.well-known/mercure',
        );

        return new LiveExtension($hub, $loggedUser, new LiveTopics(new StubCurrentRolesProvider()), $enabled);
    }
}
