<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Shared;

use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Step\Then;
use Behat\Step\When;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * This context class contains the definitions of the steps used by the demo
 * feature file. Learn how to get started with Behat and BDD on Behat's website.
 *
 * @see http://behat.org/en/latest/quick_start.html
 */
final readonly class ApiContext implements Context
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
    public function iMakeAPreflightCORSRquestToFrom(string $path, string $origin): void
    {
        $this->client->request('OPTIONS', $path, [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ORIGIN' => $origin,
        ]);
    }

    #[When('I am authenticated in API as :username')]
    public function iAmAuthenticatedInApiAs(string $username): void
    {
        $user = $this->getUserRepository()->findByUsername($username);

        Assert::assertNotNull($user);

        $this->client->loginUserInApi(
            new SecurityUser($user),
        );
    }

    #[When('I have an expired token for :username')]
    public function iHaveAnExpiredTokenFor(string $username): void
    {
        $user = $this->getUserRepository()->findByUsername($username);

        Assert::assertNotNull($user);

        $encoder = $this->client->getContainer()->get(JWTEncoderInterface::class);

        $token = $encoder->encode([
            'username' => $user->username(),
            'roles' => $user->roles(),
            'iat' => 1516239022, // 18/01/2018 00:30:22
            'exp' => 1516239022, // 18/01/2018 00:30:22
        ]);

        $this->client->setBearerToken($token);
    }

    #[When('I send a :method request to :path')]
    public function iSendARequestTo(string $method, string $path): void
    {
        $this->client->request($method, $path);
    }

    #[When('I send a :method request to :path with body:')]
    public function iSendARequestToWithBody(string $method, string $path, PyStringNode $content): void
    {
        $this->client->request($method, $path, [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) $content);
    }

    #[Then('the response JSON should be equal to:')]
    public function theResponseJsonShouldBeEqualTo(PyStringNode $content): void
    {
        $expected = json_encode(json_decode((string) $content), JSON_PRETTY_PRINT);
        $response = $this->client->getResponse()->getContent();

        Assert::assertNotFalse($response);

        $jsonResponse = json_decode($response, true);

        Assert::assertNotFalse($jsonResponse);

        $actual = json_encode($jsonResponse, JSON_PRETTY_PRINT);

        Assert::assertEquals($expected, $actual);
    }

    #[Then('the response JSON should be similar to:')]
    public function theResponseJsonShouldBeSimilarTo(PyStringNode $content): void
    {
        $expected = json_decode((string) $content);
        $response = $this->client->getResponse()->getContent();

        Assert::assertNotFalse($response);

        $actual = json_decode($response, true);

        foreach ($expected as $attr => $value) {
            Assert::assertTrue(isset($actual->$attr) || isset($actual[$attr]));
        }
    }

    #[Then('the status code should be :code')]
    public function theStatusCodeShouldBe(int $expectedCode): void
    {
        $exception = $this->client->getException();

        if (null == $exception) {
            $code = $this->client->getResponse()->getStatusCode();
        } else {
            $code = match ($exception::class) {
                NotFoundHttpException::class => 404,
                default => $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : $exception->getCode(),
            };
        }

        Assert::assertEquals($expectedCode, $code, $exception instanceof \Exception ? $exception->getMessage() : 'Sin excepcion');
    }

    #[Then('the response header :header should be :value')]
    public function theResponseHeaderShouldBe(string $header, string $value): void
    {
        $actual = $this->client->getResponse()->headers->get($header);

        Assert::assertEquals($value, $actual);
    }

    #[Then('the response header :header should exist')]
    public function theResponseHeaderShouldExist(string $header): void
    {
        $actual = $this->client->getResponse()->headers->has($header);

        Assert::assertTrue($actual, sprintf('The header "%s" does not exist in the response.', $header));
    }
}
