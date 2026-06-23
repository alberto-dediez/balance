<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Infrastructure\Notification;

use App\Notifications\Application\Notification\Deliver\DeliverNotificationRequest;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Notifications\Infrastructure\Notification\DispatchingSystemNotifier;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Translation\Loader\ArrayLoader as TranslationArrayLoader;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Component\Translation\Translator;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class DispatchingSystemNotifierTest extends TestCase
{
    private SpyDeliverNotificationHandler $spy;

    protected function setUp(): void
    {
        CommandBusDispatcher::instance()->reset();
        $this->spy = new SpyDeliverNotificationHandler();
        CommandBusDispatcher::instance()->subscribe($this->spy);
    }

    public function testRendersTemplateAndDispatchesSingleEmailDelivery(): void
    {
        $recipient = UserMother::from(UserId::generate(), 'recipient@example.com');
        $sut = $this->notifier($recipient, ['tpl.html.twig' => '<p>Hola {{ name }}</p>']);

        $sut->notify(new SystemNotification(
            recipientId: $recipient->id(),
            channel: NotificationChannel::Email,
            subject: 'Asunto',
            template: 'tpl.html.twig',
            params: ['name' => 'Ana'],
        ));

        $request = $this->dispatchedRequest();

        $this->assertCount(1, $this->spy->requests());
        $this->assertEquals(NotificationChannel::Email, $request->channel);
        $this->assertEquals('<p>Hola Ana</p>', $request->body);
    }

    public function testInternalChannelIsSkippedWhenDisabled(): void
    {
        $recipient = UserMother::from(UserId::generate(), 'recipient@example.com');
        $sut = $this->notifier($recipient, [], internalNotificationsEnabled: false);

        $sut->notify(new SystemNotification(
            recipientId: $recipient->id(),
            channel: NotificationChannel::Internal,
            subject: 'Asunto',
            body: 'Mensaje',
        ));

        $this->assertCount(0, $this->spy->requests());
    }

    public function testInternalChannelUsesPlainBodyWhenEnabled(): void
    {
        $recipient = UserMother::from(UserId::generate(), 'recipient@example.com');
        $sut = $this->notifier($recipient, []);

        $sut->notify(new SystemNotification(
            recipientId: $recipient->id(),
            channel: NotificationChannel::Internal,
            subject: 'Asunto',
            body: 'Mensaje',
        ));

        $request = $this->dispatchedRequest();

        $this->assertCount(1, $this->spy->requests());
        $this->assertEquals(NotificationChannel::Internal, $request->channel);
        $this->assertEquals('Mensaje', $request->body);
    }

    public function testDoesNotDispatchWhenRecipientIsUnknown(): void
    {
        $sut = $this->notifier(null, []);

        $sut->notify(new SystemNotification(
            recipientId: UserId::generate(),
            channel: NotificationChannel::Email,
            subject: 'Asunto',
            body: 'Mensaje',
        ));

        $this->assertCount(0, $this->spy->requests());
    }

    public function testLocalizesSubjectAndBodyToRecipientPreferredLocale(): void
    {
        $recipient = UserMother::from(UserId::generate(), 'recipient@example.com');
        $recipient->changePreferredLocale(Locale::English);

        $translator = new Translator('es');
        $translator->addLoader('array', new TranslationArrayLoader());
        $translator->addResource('array', ['Asunto' => 'Subject'], 'en');

        $sut = $this->notifier(
            $recipient,
            ['tpl.html.twig' => '{{ "Cuerpo"|trans }}'],
            translator: $translator,
        );

        $sut->notify(new SystemNotification(
            recipientId: $recipient->id(),
            channel: NotificationChannel::Email,
            subject: 'Asunto',
            template: 'tpl.html.twig',
        ));

        $request = $this->dispatchedRequest();

        $this->assertEquals('Subject', $request->subject);
        $this->assertEquals('es', $translator->getLocale(), 'The ambient locale is restored after dispatching.');
    }

    /**
     * @param array<string,string> $templates
     */
    private function notifier(?User $recipient, array $templates, bool $internalNotificationsEnabled = true, ?Translator $translator = null): DispatchingSystemNotifier
    {
        $userRepository = new InMemoryUserRepository();

        if ($recipient instanceof User) {
            $userRepository->add($recipient);
        }

        $translator ??= new Translator('es');
        $twig = new Environment(new ArrayLoader($templates));
        $twig->addExtension(new TranslationExtension($translator));

        return new DispatchingSystemNotifier(
            $twig,
            $userRepository,
            $translator,
            new LocaleSwitcher('es', [$translator]),
            $internalNotificationsEnabled,
        );
    }

    private function dispatchedRequest(): DeliverNotificationRequest
    {
        return $this->spy->requests()[0];
    }
}
