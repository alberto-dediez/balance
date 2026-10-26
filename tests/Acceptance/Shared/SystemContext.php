<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Shared;

use App\Shared\Infrastructure\Health\InMemoryHealthIndicator;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

class SystemContext implements Context
{
    private readonly Application $application;
    private ?string $output = null;
    private ?int $statusCode = null;
    private ?CommandTester $commandTester = null;

    /** @var list<string> */
    private array $answers = [];

    public function __construct(private readonly KernelInterface $kernel)
    {
        $this->application = new Application($kernel);
    }

    #[AfterScenario]
    public function after(AfterScenarioScope $scope): void
    {
        $this->getInMemoryHealthIndicator()->markHealthy();
    }

    private function getInMemoryHealthIndicator(): InMemoryHealthIndicator
    {
        return $this->kernel->getContainer()->get(InMemoryHealthIndicator::class);
    }

    #[Given('the health indicator :name is failing')]
    public function theHealthIndicatorIsFailing(string $name): void
    {
        $indicator = $this->getInMemoryHealthIndicator();

        Assert::assertSame($name, $indicator->name());

        $indicator->markUnhealthy();
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
        Assert::assertNotEmpty($this->output);

        Assert::assertStringContainsString($text, $this->output);
    }

    #[Then("I don't want to see :text in command output")]
    public function iDontWantToSeeInCommandOutput(string $text): void
    {
        Assert::assertNotEmpty($this->output);

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

    #[Given('the command answers are:')]
    public function theCommandAnswersAre(TableNode $table): void
    {
        $this->answers = array_map(
            static fn(array $row): string => (string) $row['answer'],
            $table->getColumnsHash(),
        );
    }

    #[When('command :name is executed with parameters:')]
    public function commandIsExecutedWithParameters(string $name, TableNode $table): void
    {
        $parameters = [];

        foreach ($table->getColumnsHash() as $row) {
            $parameter = (string) $row['parameter'];
            $value = '' === (string) $row['value'] && str_starts_with($parameter, '--') ? true : (string) $row['value'];

            if (!array_key_exists($parameter, $parameters)) {
                $parameters[$parameter] = $value;

                continue;
            }

            $parameters[$parameter] = array_merge((array) $parameters[$parameter], [$value]);
        }

        $command = $this->application->find($name);

        $this->commandTester = new CommandTester($command);
        $this->commandTester->setInputs($this->answers);
        $this->statusCode = $this->commandTester->execute($parameters, ['interactive' => [] !== $this->answers]);
        $this->answers = [];

        $this->output = $this->commandTester->getDisplay();
    }

    #[Then('Is Executed Successfully')]
    public function isExecutedSuccessfully(): void
    {
        Assert::assertNotNull($this->commandTester);

        $this->commandTester->assertCommandIsSuccessful();
    }

    #[Then('I expect status code :code')]
    public function iExpectStatusCode(int $code): void
    {
        Assert::assertEquals($code, $this->statusCode);
    }
}
