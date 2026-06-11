<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Notification;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionCompleted;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionValidated;
use App\FoodLoss\Infrastructure\Notification\PreventionPlanVersionCompletedMailer;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSender;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class PreventionPlanVersionCompletedMailerTest extends TestCase
{
    private PreventionPlanVersionCompletedMailer $sut;
    private PreventionPlanRepository $preventionPlanRepository;
    private UserRepository $userRepository;
    private SpyEmailSender $spyEmailSender;
    private PreventionPlanVersionCompleted $event;
    private User $creator;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->preventionPlanRepository = PreventionPlanRepositoryMother::inMemory();
        $this->userRepository = UserRepositoryMother::inMemory();
        $this->spyEmailSender = new SpyEmailSender();

        $this->creator = UserMother::from(userId: new UserId('user-id'));
        $this->userRepository->add($this->creator);

        $plan = PreventionPlanMother::registeredPreventionPlan();
        $this->preventionPlanRepository->add($plan);

        $this->event = new PreventionPlanVersionCompleted(
            preventionPlanId: $plan->id(),
            versionNumber: $plan->currentVersion(),
        );

        $this->sut = $this->createSUT();
    }

    public function testIsSubscribedToPreventionPlanVersionCompleted(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo($this->event));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $this->assertFalse($this->sut->isSubscribedTo(new PreventionPlanVersionValidated(
            PreventionPlanId::generate(),
            1,
        )));
    }

    public function testSendsEmailToVersionCreator(): void
    {
        $this->sut->handle($this->event);

        $this->assertTrue($this->spyEmailSender->wasCalled());
        $this->assertEquals($this->creator->username(), $this->spyEmailSender->lastRecipient()?->email);
    }

    public function testDoesNothingWhenPreventionPlanNotFound(): void
    {
        $unknownEvent = new PreventionPlanVersionCompleted(
            preventionPlanId: PreventionPlanId::generate(),
            versionNumber: 1,
        );

        $this->sut->handle($unknownEvent);

        $this->assertFalse($this->spyEmailSender->wasCalled());
    }

    public function testDoesNothingWhenVersionNotFound(): void
    {
        $plan = PreventionPlanMother::registeredPreventionPlan();
        $this->preventionPlanRepository->add($plan);

        $eventForMissingVersion = new PreventionPlanVersionCompleted(
            preventionPlanId: $plan->id(),
            versionNumber: 99,
        );

        $this->sut->handle($eventForMissingVersion);

        $this->assertFalse($this->spyEmailSender->wasCalled());
    }

    public function testDoesNothingWhenCreatorUserNotFound(): void
    {
        $emptyUserRepository = UserRepositoryMother::inMemory();

        $sut = new PreventionPlanVersionCompletedMailer(
            emailSender: $this->spyEmailSender,
            preventionPlanRepository: $this->preventionPlanRepository,
            userRepository: $emptyUserRepository,
            translator: $this->createTranslator(),
        );

        $sut->handle($this->event);

        $this->assertFalse($this->spyEmailSender->wasCalled());
    }

    private function createSUT(): PreventionPlanVersionCompletedMailer
    {
        return new PreventionPlanVersionCompletedMailer(
            emailSender: $this->spyEmailSender,
            preventionPlanRepository: $this->preventionPlanRepository,
            userRepository: $this->userRepository,
            translator: $this->createTranslator(),
        );
    }

    private function createTranslator(): TranslatorInterface
    {
        return new class implements TranslatorInterface {
            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return $id;
            }

            public function getLocale(): string
            {
                return 'es';
            }
        };
    }
}

class SpyEmailSender implements EmailSender
{
    private bool $called = false;
    private ?EmailRecipient $lastRecipient = null;

    public function __invoke(EmailRecipient $recipient, string $subject, string $template, ?array $params = null): void
    {
        $this->called = true;
        $this->lastRecipient = $recipient;
    }

    public function wasCalled(): bool
    {
        return $this->called;
    }

    public function lastRecipient(): ?EmailRecipient
    {
        return $this->lastRecipient;
    }
}
