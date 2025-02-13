<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Shared;

use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Behat\Behat\Tester\Exception\PendingException;

/**
 * This context class contains the definitions of the steps used by the demo
 * feature file. Learn how to get started with Behat and BDD on Behat's website.
 *
 * @see http://behat.org/en/latest/quick_start.html
 */
final class ApiContext implements Context
{
    public function __construct(private KernelBrowser $client)
    {
    }

    #[When('I send a :method request to :path')]
    public function iSendARequestTo($method, $path)
    {
        $this->client->request($method, $path);
    }

    #[When('I send a :method request to :path with body:')]
    public function iSendARequestToWithBody($method, $path, PyStringNode $content)
    { 
        $this->client->request($method, $path, [], [], [], (string) $content);
    }

    #[Then('the response JSON should be equal to:')]
    public function theResponseJsonShouldBeEqualTo(PyStringNode $content)
    {
        $expected = json_encode(json_decode((string) $content), JSON_PRETTY_PRINT);
        $actual = json_encode(json_decode($this->client->getResponse()->getContent(), true), JSON_PRETTY_PRINT);

        Assert::assertEquals($expected, $actual);
    }

    #[Then('the response JSON should be similar to:')]
    public function theResponseJsonShouldBeSimilarTo(PyStringNode $content)
    {
        $expected = json_decode((string) $content);
        $actual = json_decode($this->client->getResponse()->getContent(), true);

        foreach ($expected as $attr => $value) {
            Assert::assertTrue(isset($actual->$attr) || isset($actual[$attr]));
        }
    }

    #[Then('the status code should be :code')]
    public function theStatusCodeShouldBe(int $code)
    {
        Assert::assertEquals($code, $this->client->getResponse()->getStatusCode());
    }
}
