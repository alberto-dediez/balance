<?php

namespace App\Tests\Unit\Shared\Domain\Model\Email;

use App\Shared\Domain\Model\Email\EmailSent;
use App\Shared\Domain\Model\Email\EmailSender;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Email\EmailRecipient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;

abstract class EmailSenderTest extends KernelTestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $sut = $this->createSUT();

        $recipient = new EmailRecipient('name of recipient', 'recipient@email.com');

        $sut($recipient, 'about email', '@shared/layout/email.html.twig');

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EmailSent);
        $this->assertEquals($recipient, $lastEvent->recipient());
    }

    abstract public function createSUT(): EmailSender;
}