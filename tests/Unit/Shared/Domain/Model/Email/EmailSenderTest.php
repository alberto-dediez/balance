<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Email;

use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Email\EmailAttachment;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSender;
use App\Shared\Domain\Model\Email\EmailSent;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class EmailSenderTest extends KernelTestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $sut = $this->createSUT();

        $recipient = new EmailRecipient('name of recipient', 'recipient@email.com');

        $sut($recipient, 'about email', '@shared/layout/email.html.twig');

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EmailSent);
        $this->assertEquals($recipient, $lastEvent->recipient());
    }

    public function testSendingWithAnAttachment(): void
    {
        $sut = $this->createSUT();

        $path = sprintf('%s/%s.pdf', sys_get_temp_dir(), uniqid('email-attachment-', true));
        file_put_contents($path, 'pdf');

        $recipient = new EmailRecipient('name of recipient', 'recipient@email.com');

        try {
            $sut(
                $recipient,
                'about email',
                '@shared/layout/email.html.twig',
                null,
                [new EmailAttachment('certificate.pdf', $path)],
            );
        } finally {
            @unlink($path);
        }

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(EmailSent::class, $lastEvent);
        $this->assertEquals($recipient, $lastEvent->recipient());
    }

    abstract public function createSUT(): EmailSender;
}
