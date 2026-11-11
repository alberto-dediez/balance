<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\ApiToken\MarkAsUsed;

use App\Security\Application\ApiToken\MarkAsUsed\MarkApiTokenAsUsedRequest;
use App\Security\Application\ApiToken\MarkAsUsed\MarkApiTokenAsUsedService;
use App\Security\Domain\Model\ApiToken\ApiTokenId;
use App\Security\Domain\Model\ApiToken\ApiTokenUsed;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenMother;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class MarkApiTokenAsUsedServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testMarksTheTokenOfTheUserAsUsed(): void
    {
        $repository = ApiTokenRepositoryMother::inMemory();
        $user = UserMother::adminUser();
        $apiToken = ApiTokenMother::active($user->id());
        $repository->add($apiToken);

        $this->spySubscriber = new SpyDomainEventSubscriber();

        $result = (new MarkApiTokenAsUsedService($repository))(new MarkApiTokenAsUsedRequest($apiToken->id()), $user);

        $this->assertTrue($result->marked);
        $this->assertNotNull($apiToken->lastUsedAt());
        $this->assertInstanceOf(ApiTokenUsed::class, $this->spySubscriber->lastEvent());
    }

    public function testDoesNotMarkTheTokenOfAnotherUser(): void
    {
        $repository = ApiTokenRepositoryMother::inMemory();
        $apiToken = ApiTokenMother::active(UserMother::fromUsername('other@example.com')->id());
        $repository->add($apiToken);

        $this->spySubscriber = new SpyDomainEventSubscriber();

        $result = (new MarkApiTokenAsUsedService($repository))(new MarkApiTokenAsUsedRequest($apiToken->id()), UserMother::adminUser());

        $this->assertFalse($result->marked);
        $this->assertNull($apiToken->lastUsedAt());
        $this->assertEmpty($this->spySubscriber->events());
    }

    public function testUnknownTokenIsNotMarked(): void
    {
        $result = (new MarkApiTokenAsUsedService(ApiTokenRepositoryMother::inMemory()))(new MarkApiTokenAsUsedRequest(ApiTokenId::generate()), UserMother::adminUser());

        $this->assertFalse($result->marked);
    }
}
