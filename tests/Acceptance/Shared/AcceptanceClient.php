<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Shared;

use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Link;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profile;

final class AcceptanceClient
{
    private static ?AcceptanceClient $instance = null;

    private ?Crawler $crawler = null;

    /** @var array<string, string> */
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
        if (!self::$instance instanceof AcceptanceClient) {
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

    /**
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $files
     */
    public function requestWithFiles(string $method, string $path, array $parameters = [], array $files = []): void
    {
        try {
            $this->crawler = $this->client->request($method, $path, $parameters, $files, $this->server);

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

    public function click(Link $link): void
    {
        try {
            $this->crawler = $this->client->click($link);

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

        return $this->crawler ?? new Crawler(false !== $response ? $response : '');
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

    public function getKernelBrowser(): KernelBrowser
    {
        return $this->client;
    }

    public function reset(): void
    {
        $this->server = [];
        $this->user = null;

        $this->client->getCookieJar()->clear();
        $this->client->getHistory()->clear();
    }
}
