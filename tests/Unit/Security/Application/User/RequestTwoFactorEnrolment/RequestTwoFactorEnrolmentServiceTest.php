<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\RequestTwoFactorEnrolment;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\RequestTwoFactorEnrolment\RequestTwoFactorEnrolmentRequest;
use App\Security\Application\User\RequestTwoFactorEnrolment\RequestTwoFactorEnrolmentService;
use App\Security\Domain\Model\User\TwoFactorEnrolmentConfirmed;
use App\Security\Domain\Model\User\TwoFactorEnrolmentRequested;
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\InMemoryTotpAuthenticator;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RequestTwoFactorEnrolmentServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testEmailIsEnabledDirectly(): void
    {
        $repository = new InMemoryUserRepository();
        $user = UserMother::fromUsername('user@dediez.software');
        $repository->add($user);

        $response = $this->createSUT($repository)(new RequestTwoFactorEnrolmentRequest($user->id(), TwoFactorMethod::Email), $user);

        $this->assertEquals(TwoFactorMethod::Email, $response->method);
        $this->assertSame('', $response->provisioningUri);
        $this->assertSame('', $response->manualKey);
        $this->assertEquals(TwoFactorMethod::Email, $user->twoFactorMethod());
        $this->assertInstanceOf(TwoFactorEnrolmentConfirmed::class, $this->spy->lastEvent());
    }

    public function testTotpReturnsTheProvisioningData(): void
    {
        $repository = new InMemoryUserRepository();
        $user = UserMother::fromUsername('user@dediez.software');
        $repository->add($user);

        $response = $this->createSUT($repository)(new RequestTwoFactorEnrolmentRequest($user->id(), TwoFactorMethod::Totp), $user);

        $this->assertEquals(TwoFactorMethod::Totp, $response->method);
        $this->assertEquals('otpauth://totp/test', $response->provisioningUri);
        $this->assertEquals('INMEMORYSECRET', $response->manualKey);
        $this->assertNull($user->twoFactorMethod());
        $this->assertTrue($user->hasPendingTotpEnrolment());
        $this->assertInstanceOf(TwoFactorEnrolmentRequested::class, $this->spy->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryUserRepository();
        $user = UserMother::fromUsername('user@dediez.software');
        $repository->add($user);

        $this->createSUT($repository)(new RequestTwoFactorEnrolmentRequest($user->id(), TwoFactorMethod::Email), UserMother::ownerUser());
    }

    private function createSUT(InMemoryUserRepository $repository): RequestTwoFactorEnrolmentService
    {
        return new RequestTwoFactorEnrolmentService(
            repository: $repository,
            authorizer: UserAuthorizerMother::create(),
            totp: new InMemoryTotpAuthenticator(),
            issuer: 'Hydra',
        );
    }
}
