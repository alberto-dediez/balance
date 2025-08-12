<?php

namespace App\Tests\Acceptance\Shared;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

final class AcceptanceClient
{
    protected static $instance;

    private $crawler = null;

    private array $server = [];

    private ?SecurityUser $user = null;
    private ?SecurityUser $apiUser = null;

    private ?\Exception $exception = null;  
    
    private function __construct(private KernelBrowser $client)
    {
        $this->client->catchExceptions(false);     
        $this->client->disableReboot();
    }

    public static function instance(KernelBrowser $client): AcceptanceClient
    {
        if (null === static::$instance) {
            static::$instance = new self($client);
        }

        return static::$instance;
    }

    public function request(string $method, string $path, ?array $server = null, $body = null): void
    {
        $server = null == $server ? ($this->server) : array_merge($server, $this->server);

        try {
            $this->crawler = $this->client->request($method, $path, [], [], $server, $body);            

            $this->exception = null;
        } catch (\Exception $e) {
            $this->exception = $e;
        }
    }

    public function submit($formData)
    {
        try {
            $this->crawler = $this->client->submit($formData);
        
            $this->exception = null;
        } catch (\Exception $e) {
            $this->exception = $e;
        }
    }

    public function followRedirect()
    {
        $this->client->followRedirect();
    }

    public function getRequest()
    {
        return $this->client->getRequest();
    }

    public function getResponse(): ?Response
    {
        return $this->client->getResponse();
    }

    public function getCrawler()
    {
        return null == $this->crawler ? 
            new Crawler($this->client->getResponse()->getContent())
            : $this->crawler
        ;
    }

    public function getException(): ?\Exception
    {
        return $this->exception;
    }

    public function getProfile()
    {
        return $this->client->getProfile();
    }

    public function getContainer()
    {
        $container = $this->client->getContainer();

        return $container->has('test.service_container') ? $container->get('test.service_container') : $container;
    }

    public function loginUser(SecurityUser $user)
    {
        $this->user = $user;

        $this->client->loginUser($this->user);
    }

    public function loginUserInApi(SecurityUser $user)
    {
        $this->apiUser = $user;

        $encoder = $this->getContainer()->get(JWTEncoderInterface::class);

        $this->setBearerToken($encoder->encode([
            'username' => $user->username(),
            'roles' => $user->getRoles(),
        ]));
    }

    public function setBearerToken($token) 
    {
        $this->server['HTTP_AUTHORIZATION'] = sprintf('Bearer %s', $token);
    }

    public function reset()
    {
        $this->apiUser = null;
        $this->server = [];
        $this->user = null;

        $this->client->getCookieJar()->clear();
        $this->client->getHistory()->clear();
    }
}