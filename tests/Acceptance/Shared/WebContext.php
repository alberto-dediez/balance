<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Shared;

use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;
use Behat\Behat\Context\Context;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;

/**
 * This context class contains the definitions of the steps used by the demo
 * feature file. Learn how to get started with Behat and BDD on Behat's website.
 *
 * @see http://behat.org/en/latest/quick_start.html
 */
final class WebContext implements Context
{
    public function __construct(private KernelBrowser $client, private UserRepository $userRepository)
    {
    }


    #[When('I am logged as :username')]
    public function iAmLoggedAs($username)
    {
        $user = $this->userRepository->findByUsername($username);

        $securityUser = new SecurityUser($user);

        $this->client->loginUser($securityUser);
    }

    #[When('I go to :path')]
    public function iGoTo($path)
    {
        $this->client->request('GET', $path);
    }

    #[Then('the response status should be :code')]
    public function theResponseStatusShouldBe(int $code)
    {
        Assert::assertEquals($code, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
    }

    #[Then('I am redirected to :path')]
    public function iAmRedirectedTo($path)
    {
        $this->client->followRedirect();

        Assert::assertEquals($path, $this->client->getRequest()->getPathInfo());
    }

    #[Then('I see a button with name :name')]
    public function iSeeAButtonWithName($name)
    {
        $crawler = $this->getCrawler();

        $button = $crawler->selectButton($name);

        Assert::assertCount(1, $button);
    }

    #[Then('I see a table with :rows rows')]
    public function iSeeATableWithRows($rows)
    {
        $crawler = $this->getCrawler();

        $nodes = $crawler->filter('table tr');

        Assert::assertCount((int) $rows, $nodes);
    }

    #[Then('I see a text :text')]
    public function iSeeAText($text)
    {
        Assert::assertStringContainsString($text, $this->client->getResponse()->getContent());
    }

    private function getCrawler()
    {
        return new Crawler($this->client->getResponse()->getContent());
    }
}
