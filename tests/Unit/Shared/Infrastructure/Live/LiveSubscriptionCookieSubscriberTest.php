<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Live;

use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\Live\LiveTopics;
use App\Shared\Infrastructure\Live\LiveSubscriptionCookieSubscriber;
use App\Tests\Unit\Development\Infrastructure\Mercure\SpyTokenFactory;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use App\Tests\Unit\Shared\Application\CurrentRoles\StubCurrentRolesProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Jwt\Grant;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\ProtocolVersion;
use Symfony\Component\Mercure\Update;

class LiveSubscriptionCookieSubscriberTest extends TestCase
{
    private SpyTokenFactory $tokenFactory;

    protected function setUp(): void
    {
        $this->tokenFactory = new SpyTokenFactory();
    }

    public function testSetsTheSubscriptionCookieForAnAuthenticatedUser(): void
    {
        $user = UserMother::adminUser();

        $this->createSUT($user)->onResponse($this->responseEvent());

        $this->assertCount(1, $this->tokenFactory->tokens);

        $grants = $this->tokenFactory->tokens[0]['grants'];

        $this->assertCount(1, $grants);
        $this->assertContainsOnlyInstancesOf(Grant::class, $grants);
        $this->assertSame([Grant::ACTION_SUBSCRIBE], $grants[0]->actions);
        $this->assertSame(['/live/admin', '/live/users/' . $user->id()], $grants[0]->topics);
        $this->assertArrayHasKey('exp', $this->tokenFactory->tokens[0]['claims']);
    }

    public function testASimpleUserDoesNotGetTheAdminTopic(): void
    {
        $user = UserMother::registeredUser();

        $this->createSUT($user)->onResponse($this->responseEvent());

        $this->assertSame(['/live/users/' . $user->id()], $this->tokenFactory->tokens[0]['grants'][0]->topics);
    }

    public function testDoesNothingWithoutUser(): void
    {
        $this->createSUT(null)->onResponse($this->responseEvent());

        $this->assertSame([], $this->tokenFactory->tokens);
    }

    public function testDoesNothingOutsideTheMainFirewall(): void
    {
        $this->createSUT(UserMother::adminUser(), 'api')->onResponse($this->responseEvent());

        $this->assertSame([], $this->tokenFactory->tokens);
    }

    public function testDoesNothingOnSubRequests(): void
    {
        $this->createSUT(UserMother::adminUser())->onResponse($this->responseEvent(HttpKernelInterface::SUB_REQUEST));

        $this->assertSame([], $this->tokenFactory->tokens);
    }

    public function testDoesNothingOnRedirections(): void
    {
        $this->createSUT(UserMother::adminUser())->onResponse($this->responseEvent(response: new Response('', 302)));

        $this->assertSame([], $this->tokenFactory->tokens);
    }

    public function testDoesNothingWhenDisabled(): void
    {
        $this->createSUT(UserMother::adminUser(), enabled: false)->onResponse($this->responseEvent());

        $this->assertSame([], $this->tokenFactory->tokens);
    }

    public function testDoesNotFailWhenTheHubIsOnAnotherDomain(): void
    {
        $event = $this->responseEvent();

        $this->createSUT(UserMother::adminUser(), hubUrl: 'https://hub.other-domain.org/.well-known/mercure')->onResponse($event);

        $this->assertSame([], $event->getRequest()->attributes->get('_mercure_authorization_cookies', []));
    }

    private function responseEvent(int $requestType = HttpKernelInterface::MAIN_REQUEST, ?Response $response = null): ResponseEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new ResponseEvent($kernel, Request::create('https://example.com/'), $requestType, $response ?? new Response());
    }

    private function createSUT(?User $user, string $firewall = 'main', bool $enabled = true, string $hubUrl = 'https://example.com/.well-known/mercure'): LiveSubscriptionCookieSubscriber
    {
        $security = $this->createMock(Security::class);
        $loggedUser = new LoggedUser($security, UserRepositoryMother::inMemory());
        $loggedUser->forceUser($user);
        $security->method('getFirewallConfig')->willReturn(new FirewallConfig($firewall, 'user_checker'));

        $hub = new MockHub(
            $hubUrl,
            new StaticTokenProvider('token'),
            static fn(Update $update): string => 'id',
            $this->tokenFactory,
            protocolVersion: ProtocolVersion::Legacy,
        );

        return new LiveSubscriptionCookieSubscriber(
            new Authorization(new HubRegistry($hub)),
            $security,
            $loggedUser,
            new LiveTopics(new StubCurrentRolesProvider()),
            new NullLogger(),
            $enabled,
        );
    }
}
