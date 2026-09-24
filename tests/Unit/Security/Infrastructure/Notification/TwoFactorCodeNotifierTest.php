<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Notification;

use App\Security\Domain\Model\User\TwoFactorChallenge;
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Infrastructure\Notification\TwoFactorCodeNotifier;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Notifications\Application\Notification\Deliver\SpyEmailSender;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\IdentityTranslator;

class TwoFactorCodeNotifierTest extends TestCase
{
    private SpyEmailSender $emailSender;

    private InMemoryUserRepository $repository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->emailSender = new SpyEmailSender();
        $this->repository = new InMemoryUserRepository();

        new TwoFactorCodeNotifier($this->emailSender, $this->repository, new IdentityTranslator());
    }

    public function testEmailChallengeIsSentToTheUsername(): void
    {
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email);
        $this->repository->add($user);

        $challenge = TwoFactorChallenge::generate('app-secret');
        $user->requestTwoFactorChallenge(TwoFactorMethod::Email, $challenge, 'u***@dediez.software');

        $sent = $this->emailSender->sent();

        $this->assertCount(1, $sent);
        $this->assertEquals('user@dediez.software', $sent[0]['recipient']->email);
        $this->assertEquals('Código de acceso', $sent[0]['subject']);
        $this->assertEquals('@security/two-factor/code.email.html.twig', $sent[0]['template']);
        $this->assertEquals($challenge->plainCode(), $sent[0]['params']['otpCode'] ?? null);
    }

    public function testChallengeWithoutCodeIsNotSent(): void
    {
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Totp);
        $this->repository->add($user);

        $user->requestTwoFactorChallenge(TwoFactorMethod::Totp, TwoFactorChallenge::withoutCode(), '');

        $this->assertCount(0, $this->emailSender->sent());
    }
}
