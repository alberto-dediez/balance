<?php

namespace App\Tests\Acceptance\Shared;

use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

final class AcceptanceClient
{
    private static ?AcceptanceClient $instance = null;

    private ?Crawler $crawler = null;

    /** @var array<string, string> $server  */
    private array $server = [];

    private ?SecurityUser $user = null;

    private ?\Exception $exception = null;

    private function __construct(private readonly KernelBrowser $client)
    {
        $this->client->catchExceptions(false);
        $this->client->disableReboot();
    }

    public static function instance(KernelBrowser $client): AcceptanceClient
    {
        if (null === self::$instance) {
            self::$instance = new self($client);
        }

        return self::$instance;
    }

    /** @param array<string, string> $server  */
    public function request(string $method, string $path, ?array $server = null, ?string $body = null): void
    {
        $server = null == $server ? ($this->server) : array_merge($server, $this->server);

        try {
            $this->crawler = $this->client->request($method, $path, [], [], $server, $body);

            $this->exception = null;
        } catch (\Exception $e) {
            $this->exception = $e;
        }
    }

    public function submit(Form $formData): void
    {
        try {
            $this->crawler = $this->client->submit($formData);

            $this->exception = null;
        } catch (\Exception $e) {
            $this->exception = $e;
        }
    }

    public function followRedirect(): void
    {
        $this->client->followRedirect();
    }

    public function getRequest(): Request
    {
        return $this->client->getRequest();
    }

    public function getResponse(): Response
    {
        return $this->client->getResponse();
    }

    public function getCrawler(): Crawler
    {
        $response = $this->client->getResponse()->getContent();

        if (null === $this->crawler && false !== $response) {
            return new Crawler($response);
        }

        return $this->crawler;
    }

    public function getException(): ?\Exception
    {
        return $this->exception;
    }

    public function getProfile(): Profile|false|null
    {
        return $this->client->getProfile();
    }

    public function getContainer(): ContainerInterface
    {
        return $this->client->getContainer();
    }

    public function loginUser(SecurityUser $user): void
    {
        $this->user = $user;

        $this->client->loginUser($this->user);
    }

    public function loginUserInApi(SecurityUser $user): void
    {
        $this->user = $user;

        $encoder = $this->getContainer()->get(JWTEncoderInterface::class);

        $this->setBearerToken($encoder->encode([
            'username' => $user->username(),
            'roles' => $user->getRoles(),
        ]));
    }

    public function setBearerToken(string $token): void
    {
        $this->server['HTTP_AUTHORIZATION'] = sprintf('Bearer %s', $token);
    }

    public function reset(): void
    {
        $this->server = [];
        $this->user = null;

        $this->client->getCookieJar()->clear();
        $this->client->getHistory()->clear();
    }
}
