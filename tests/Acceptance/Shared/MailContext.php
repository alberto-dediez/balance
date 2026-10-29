<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Shared;

use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Infrastructure\Symfony\Event\EmailListener;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Hook\BeforeScenario;
use Behat\Step\Then;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mime\Email;

/**
 * This context class contains the definitions of the steps used by the demo
 * feature file. Learn how to get started with Behat and BDD on Behat's website.
 *
 * @see http://behat.org/en/latest/quick_start.html
 */
final readonly class MailContext implements Context
{
    public function __construct(private KernelInterface $kernel) {}

    #[BeforeScenario]
    public function after(BeforeScenarioScope $scope): void
    {
        $this->getEmailListener()->reset();

        DomainEventPublisher::instance()->clearPending();
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();
    }

    private function getEmailListener(): EmailListener
    {
        return $this->kernel->getContainer()->get(EmailListener::class);
    }

    #[Then('the email with subject :subject contains :text')]
    public function theEmailWithSubjectContains(string $subject, string $text): void
    {
        Assert::assertStringContainsString($text, $this->emailBodyWithSubject($subject));
    }

    #[Then('the email with subject :subject does not contain :text')]
    public function theEmailWithSubjectDoesNotContain(string $subject, string $text): void
    {
        Assert::assertStringNotContainsString($text, $this->emailBodyWithSubject($subject));
    }

    private function emailBodyWithSubject(string $subject): string
    {
        foreach ($this->getEmailListener()->getMessages() as $message) {
            Assert::assertInstanceOf(Email::class, $message);

            if ($subject === $message->getSubject()) {
                return (string) $message->getHtmlBody();
            }
        }

        Assert::fail(sprintf('No se ha enviado ningún email con asunto "%s".', $subject));
    }

    #[Then('an email with subject :subject was sent to :address')]
    public function anEmailWithSubjectWasSentTo(string $subject, string $address): void
    {
        foreach ($this->getEmailListener()->getMessages() as $message) {
            Assert::assertInstanceOf(Email::class, $message);

            if ($subject === $message->getSubject() && $address === ($message->getTo()[0] ?? null)?->getAddress()) {
                return;
            }
        }

        Assert::fail(sprintf('No se ha enviado ningún email con asunto "%s" a "%s".', $subject, $address));
    }

    #[Then('I received an email with subject :subject to :address')]
    public function iReceivedAnEmailWithSubjectTo(string $subject, string $address): void
    {
        $messages = $this->getEmailListener()->getMessages();

        Assert::assertGreaterThanOrEqual(1, count($messages));

        /** @var Email $lastMessage */
        $lastMessage = $messages[count($messages) - 1];

        Assert::assertInstanceOf(Email::class, $lastMessage, 'El mensaje no es una instancia de Symfony\Component\Mime\Email.');

        Assert::assertEquals($address, $lastMessage->getTo()[0]->getAddress());
        Assert::assertEquals($subject, $lastMessage->getSubject());
    }
}
