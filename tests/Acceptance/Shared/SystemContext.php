<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Shared;

use Behat\Step\Then;
use Behat\Step\When;
use Behat\Step\Given;
use PHPUnit\Framework\Assert;
use Behat\Behat\Context\Context;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class SystemContext implements Context
{
    private Application $application;
    private ?string $output;
    private ?int $statusCode;
    private ?CommandTester $commandTester;

    public function __construct(private KernelInterface $kernel)
    {
        $this->application = new Application($kernel);
    }

    #[Given('I am the system')]
    public function iAmTheSystem()
    {
        Assert::assertEquals('cli', php_sapi_name());
    }

    #[When('command :name is executed')]
    public function commandIsExecuted(string $name)
    {
        $command = $this->application->find($name);

        $this->commandTester = new CommandTester($command);
        $this->statusCode = $this->commandTester->execute([]);

        $this->output = $this->commandTester->getDisplay();
    }

    #[Then('I want to see :text in command output')]
    public function iWantToSeeInCommandOutput(string $text)
    {
        Assert::assertStringContainsString($text, $this->output);
    }

    #[Then("I don't want to see :text in command output")]
    public function iDontWantToSeeInCommandOutput($text)
    {
        Assert::assertStringNotContainsString($text, $this->output);
    }

    #[When('command :name is executed with argument :argument')]
    public function commandIsExecutedWithArgument(string $name, string $argument)
    {
        $parameters = [];

        $arguments = explode(' ', $argument);

        if (count($arguments) > 0) {
            foreach ($arguments as $part) {
                list($attr, $value) = explode(':', $part);

                $parameters[$attr] = $value;
            }
        }

        $command = $this->application->find($name);

        $this->commandTester = new CommandTester($command);
        $this->statusCode = $this->commandTester->execute($parameters);

        $this->output = $this->commandTester->getDisplay();
    }

    #[Then("Is Executed Successfully")]
    public function isExecutedSuccessfully()
    {
        $this->commandTester->assertCommandIsSuccessful();
    }

    #[Then("I expect status code :code")]
    public function iExpectStatusCode(int $code)
    {
        Assert::assertEquals($code, $this->statusCode);
    }
}
