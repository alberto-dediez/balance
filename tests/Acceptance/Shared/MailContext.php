<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Shared;

use Behat\Step\Then;
use Behat\Hook\BeforeScenario;
use PHPUnit\Framework\Assert;
use Behat\Behat\Context\Context;
use Symfony\Component\Mime\Email;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Shared\Infrastructure\Symfony\Event\EmailListener;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * This context class contains the definitions of the steps used by the demo
 * feature file. Learn how to get started with Behat and BDD on Behat's website.
 *
 * @see http://behat.org/en/latest/quick_start.html
 */
final class MailContext implements Context
{
    public function __construct(private KernelInterface $kernel)
    {

    }

    #[BeforeScenario]
    public function after(BeforeScenarioScope $scope)
    {
        $this->getEmailListener()->reset();
    }

    private function getEmailListener(): EmailListener
    {
        return $this->kernel->getContainer()->get(EmailListener::class);
    }

    #[Then('I received an email with subject :subject to :address')]
    public function iReceivedAnEmailWithSubjectTo($subject, $address): void
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
