<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Shared;

use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

/**
 * This context class contains the definitions of the steps used by the demo
 * feature file. Learn how to get started with Behat and BDD on Behat's website.
 *
 * @see http://behat.org/en/latest/quick_start.html
 */
final class ApiContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(KernelBrowser $kernel)
    {
        $this->client = AcceptanceClient::instance($kernel);
    }

    private function getUserRepository(): UserRepository
    {
        return $this->client->getContainer()->get(UserRepository::class);
    }

    #[When('I make a preflight CORS request to :path from :origin')]
    public function iMakeAPreflightCORSRquestToFrom($path, $origin): void
    {
        $this->client->request('OPTIONS', $path, [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ORIGIN' => $origin,
        ]);
    }

    #[When('I am authenticated in API as :username')]
    public function iAmAuthenticatedInApiAs($username): void
    {
        $user = $this->getUserRepository()->findByUsername($username);

        $this->client->loginUserInApi(
            new SecurityUser($user)
        );
    }

    #[When('I have an expired token for :username')]
    public function iHaveAnExpiredTokenFor($username): void
    {
        $user = $this->getUserRepository()->findByUsername($username);

        $encoder = $this->client->getContainer()->get(JWTEncoderInterface::class);

        $token = $encoder->encode([
            'username' => $user->username(),
            'roles' => $user->roles(),
            'iat' => 1516239022, // 18/01/2018 00:30:22
            'exp' => 1516239022 // 18/01/2018 00:30:22
        ]);

        $this->client->setBearerToken($token);
    }

    #[When('I send a :method request to :path')]
    public function iSendARequestTo($method, $path)
    {
        $this->client->request($method, $path);
    }

    #[When('I send a :method request to :path with body:')]
    public function iSendARequestToWithBody($method, $path, PyStringNode $content)
    { 
        $this->client->request($method, $path, [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) $content);
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
            Assert::assertTrue(isset($actual->$attr) || isset($actual[$attr]), $attr);
        }
    }

    #[Then('the status code should be :code')]
    public function theStatusCodeShouldBe(int $expectedCode)
    {
        $exception = $this->client->getException();

        if (null == $exception) {
            $code = $this->client->getResponse()->getStatusCode();
        } else {
            switch (get_class($exception)) {
                case NotFoundHttpException::class:
                    $code = 404;
                    break;
                default:
                    $code = $exception->getCode();
                    break;
            }
        }

        Assert::assertEquals($expectedCode, $code, null !== $exception ? $exception->getMessage() : 'Sin excepcion');
    }

    #[Then('the response header :header should be :value')]
    public function theResponseHeaderShouldBe($header, $value)
    {
        $actual = $this->client->getResponse()->headers->get($header);

        Assert::assertEquals($value, $actual);
    }
    
    #[Then('the response header :header should exist')]
    public function theResponseHeaderShouldExist($header)
    {
        $actual = $this->client->getResponse()->headers->has($header);

        Assert::assertTrue($actual, sprintf('The header "%s" does not exist in the response.', $header));
    }
}
