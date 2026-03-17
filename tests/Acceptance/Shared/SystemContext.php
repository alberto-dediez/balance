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
    private readonly Application $application;
    private ?string $output = null;
    private ?int $statusCode = null;
    private ?CommandTester $commandTester = null;

    public function __construct(KernelInterface $kernel)
    {
        $this->application = new Application($kernel);
    }

    #[Given('I am the system')]
    public function iAmTheSystem(): void
    {
        Assert::assertEquals('cli', php_sapi_name());
    }

    #[When('command :name is executed')]
    public function commandIsExecuted(string $name): void
    {
        $command = $this->application->find($name);

        $this->commandTester = new CommandTester($command);
        $this->statusCode = $this->commandTester->execute([]);

        $this->output = $this->commandTester->getDisplay();
    }

    #[Then('I want to see :text in command output')]
    public function iWantToSeeInCommandOutput(string $text): void
    {
        Assert::assertStringContainsString($text, $this->output);
    }

    #[Then("I don't want to see :text in command output")]
    public function iDontWantToSeeInCommandOutput(string $text): void
    {
        Assert::assertStringNotContainsString($text, $this->output);
    }

    #[When('command :name is executed with argument :argument')]
    public function commandIsExecutedWithArgument(string $name, string $argument): void
    {
        $parameters = [];

        $arguments = explode(' ', $argument);

        foreach ($arguments as $part) {
            [$attr, $value] = explode(':', $part);

            $parameters[$attr] = $value;
        }

        $command = $this->application->find($name);

        $this->commandTester = new CommandTester($command);
        $this->statusCode = $this->commandTester->execute($parameters);

        $this->output = $this->commandTester->getDisplay();
    }

    #[Then("Is Executed Successfully")]
    public function isExecutedSuccessfully(): void
    {
        $this->commandTester->assertCommandIsSuccessful();
    }

    #[Then("I expect status code :code")]
    public function iExpectStatusCode(int $code): void
    {
        Assert::assertEquals($code, $this->statusCode);
    }
}
