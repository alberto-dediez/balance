<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Symfony\Twig;

use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Twig\UserExtension;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatableMessage;
use Twig\Node\Node;

class UserExtensionTest extends TestCase
{
    public function testSystemWhenNull(): void
    {
        $sut = new UserExtension(new InMemoryUserRepository());

        $result = $sut->userName(null);

        $this->assertInstanceOf(TranslatableMessage::class, $result);
        $this->assertSame('Sistema', $result->getMessage());
    }

    public function testExistingUserIsEscaped(): void
    {
        $user = UserMother::fromUsername('<b>legacy</b>');
        $repository = new InMemoryUserRepository();
        $repository->add($user);
        $sut = new UserExtension($repository);

        $this->assertSame('&lt;b&gt;legacy&lt;/b&gt;', $sut->userName($user->id()));
    }

    public function testPlainUsernameIsUnchanged(): void
    {
        $user = UserMother::fromUsername('an@email.com');
        $repository = new InMemoryUserRepository();
        $repository->add($user);
        $sut = new UserExtension($repository);

        $this->assertSame('an@email.com', $sut->userName($user->id()));
    }

    public function testAmpersandAndQuotesAreEscaped(): void
    {
        $user = UserMother::fromUsername('o\'brien&co"x@email.com');
        $repository = new InMemoryUserRepository();
        $repository->add($user);
        $sut = new UserExtension($repository);

        $this->assertSame('o&#039;brien&amp;co&quot;x@email.com', $sut->userName($user->id()));
    }

    public function testRemovedUserIsStruck(): void
    {
        $userId = UserId::generate();
        $sut = new UserExtension(new InMemoryUserRepository());

        $result = $sut->userName($userId);

        $this->assertInstanceOf(TranslatableMessage::class, $result);
        $this->assertSame('<s>{userId}</s>', $result->getMessage());
        $this->assertSame(['{userId}' => (string) $userId], $result->getParameters());
    }

    public function testUserNameFilterIsHtmlSafe(): void
    {
        $sut = new UserExtension(new InMemoryUserRepository());

        $filters = [];
        foreach ($sut->getFilters() as $filter) {
            $filters[$filter->getName()] = $filter;
        }

        $this->assertArrayHasKey('user_name', $filters);
        $this->assertSame(['html'], $filters['user_name']->getSafe(new Node()));
    }
}
