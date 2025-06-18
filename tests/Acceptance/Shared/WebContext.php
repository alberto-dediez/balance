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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * This context class contains the definitions of the steps used by the demo
 * feature file. Learn how to get started with Behat and BDD on Behat's website.
 *
 * @see http://behat.org/en/latest/quick_start.html
 */
final class WebContext implements Context
{
    private $crawler = null;

    private ?SecurityUser $user;

    private ?\Exception $exception = null;

    public function __construct(private KernelBrowser $client, private UserRepository $userRepository)
    {
        $this->client->catchExceptions(false);        
    }

    #[When('I am logged as :username')]
    public function iAmLoggedAs($username)
    {
        $user = $this->userRepository->findByUsername($username);

        $this->user = new SecurityUser($user);

        $this->client->disableReboot();
        $this->client->loginUser($this->user);
    }

    #[When('I go to :path')]
    public function iGoTo($path)
    {
        try {
            $this->crawler = $this->client->request('GET', $path);

            $this->exception = null;
        } catch (\Exception $e) {
            $this->exception = $e;
        }
    }

    #[When('I follow redirect')]
    public function iFollowredirect()
    {
        $this->client->followRedirect();
    }

    #[When('I submit a form with :data and button :text')]
    public function iSubmitAFormWith($data, $text): void
    {
        $crawler = $this->getCrawler();

        $buttonCrawlerNode = $crawler->selectButton($text);
        $formData = $buttonCrawlerNode->form();

        $form = $crawler->filter('form');

        $arguments = explode('|', $data);

        foreach ($arguments as $argument) {
            list($name, $value) = explode(':', $argument);

            if ('[' == $name[0]) {
                $key = sprintf("%s%s", $form->attr('name'), $name);    
            } else {
                $key = sprintf("%s[%s]", $form->attr('name'), $name);
            }

            if ('[' == $value[0]) {
                $value = explode(',', substr($value, 1, -1));
            }

            $formData[$key] = $value;
        }

        $this->crawler = $this->client->submit($formData);
        $this->exception = null;
    }

    #[Then('the response status should be :code')]
    public function theResponseStatusShouldBe(int $expectedCode)
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

    #[Then('I am redirected to :path')]
    public function iAmRedirectedTo($path)
    {
        $this->client->followRedirect();

        Assert::assertEquals($path, $this->client->getRequest()->getPathInfo());
    }

    #[Then('I see a form')]
    public function iSeeAForm(): void
    {
        $crawler = $this->getCrawler();

        $forms = $crawler->filter('form');

        Assert::assertCount(1, $forms);
    }

    #[Then('I see a button with name :name')]
    public function iSeeAButtonWithName($name)
    {
        $crawler = $this->getCrawler();

        $button = $crawler->selectButton($name);

        Assert::assertCount(1, $button);
    }

    #[Then('I see a link with name :name')]
    public function iSeeALinkWithName($name)
    {
        $crawler = $this->getCrawler();

        $button = $crawler->selectLink($name);

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
        return null == $this->crawler ? 
            new Crawler($this->client->getResponse()->getContent())
            : $this->crawler
        ;
    }
}
