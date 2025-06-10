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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * This context class contains the definitions of the steps used by the demo
 * feature file. Learn how to get started with Behat and BDD on Behat's website.
 *
 * @see http://behat.org/en/latest/quick_start.html
 */
final class ApiContext implements Context
{
    private ?\Exception $exception = null;

    public function __construct(private KernelBrowser $client)
    {
        $this->client->catchExceptions(false);        
    }

    #[When('I send a :method request to :path')]
    public function iSendARequestTo($method, $path)
    {
        try {
            $this->client->request($method, $path);

            $this->exception = null;
        } catch (\Exception $e) {
            $this->exception = $e;
        }
    }

    #[When('I send a :method request to :path with body:')]
    public function iSendARequestToWithBody($method, $path, PyStringNode $content)
    { 
        try {
            $this->client->request($method, $path, [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ], (string) $content);

            $this->exception = null;
        } catch (\Exception $e) {
            $this->exception = $e;
        }
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
    public function theStatusCodeShouldBe(int $expectedCode)
    {
        if (null == $this->exception) {
            $code = $this->client->getResponse()->getStatusCode();
        } else {
            switch (get_class($this->exception)) {
                case NotFoundHttpException::class:
                    $code = 404;
                    break;
                default:
                    $code = $this->exception->getCode();
                    break;
            }
        }

        Assert::assertEquals($expectedCode, $code);
    }
}
