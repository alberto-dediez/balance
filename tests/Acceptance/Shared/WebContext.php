<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Shared;

use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * This context class contains the definitions of the steps used by the demo
 * feature file. Learn how to get started with Behat and BDD on Behat's website.
 *
 * @see http://behat.org/en/latest/quick_start.html
 */
final readonly class WebContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(KernelBrowser $client)
    {
        $this->client = AcceptanceClient::instance($client);
    }

    private function getUserRepository(): UserRepository
    {
        return $this->client->getContainer()->get(UserRepository::class);
    }

    #[When('I am logged as :username')]
    public function iAmLoggedAs(string $username): void
    {
        $user = $this->getUserRepository()->findByUsername($username);

        Assert::assertNotNull($user, 'Usuario no encontrado');

        $this->client->loginUser(
            new SecurityUser($user),
        );
    }

    #[When('I go to :path')]
    public function iGoTo(string $path): void
    {
        $this->client->request('GET', $path);
    }

    #[When('I follow redirect')]
    public function iFollowredirect(): void
    {
        $this->client->followRedirect();
    }

    #[When('I submit a form with :data and button :text')]
    public function iSubmitAFormWith(string $data, string $text): void
    {
        $crawler = $this->client->getCrawler();

        $buttonCrawlerNode = $crawler->selectButton($text);
        $formData = $buttonCrawlerNode->form();

        $form = $crawler->filter('form');

        $arguments = explode('|', $data);

        foreach ($arguments as $argument) {
            [$name, $value] = explode(':', $argument);

            $key = '[' == $name[0] ? sprintf('%s%s', $form->attr('name'), $name) : sprintf('%s[%s]', $form->attr('name'), $name);

            if ('[' == $value[0]) {
                $value = explode(',', substr($value, 1, -1));
            }

            $formData[$key] = $value;
        }

        $this->client->submit($formData);
    }

    #[Then('the response status should be :code')]
    public function theResponseStatusShouldBe(int $expectedCode): void
    {
        $exception = $this->client->getException();

        if (null == $exception) {
            $code = $this->client->getResponse()->getStatusCode();
        } else {
            $code = match ($exception::class) {
                NotFoundHttpException::class => 404,
                default => $exception->getCode(),
            };
        }

        Assert::assertEquals($expectedCode, $code, $exception instanceof \Exception ? $exception->getMessage() : 'Sin excepcion');
    }

    #[Then('I am redirected to :path')]
    public function iAmRedirectedTo(string $path): void
    {
        $this->client->followRedirect();

        Assert::assertEquals($path, $this->client->getRequest()->getPathInfo());
    }

    #[Then('I see a form')]
    public function iSeeAForm(): void
    {
        $crawler = $this->client->getCrawler();

        $forms = $crawler->filter('form');

        Assert::assertCount(1, $forms);
    }

    #[Then('I see a button with name :name')]
    public function iSeeAButtonWithName(string $name): void
    {
        $crawler = $this->client->getCrawler();

        $button = $crawler->selectButton($name);

        Assert::assertCount(1, $button);
    }

    #[Then('I see a link with name :name')]
    public function iSeeALinkWithName(string $name): void
    {
        $crawler = $this->client->getCrawler();

        $button = $crawler->selectLink($name);

        Assert::assertCount(1, $button);
    }

    #[Then('I see a table with :rows rows')]
    public function iSeeATableWithRows(int $rows): void
    {
        $crawler = $this->client->getCrawler();

        $nodes = $crawler->filter('table tr');

        Assert::assertCount($rows, $nodes);
    }

    #[Then('I see a JSON that should be equal to:')]
    public function iSeeAJsonThatShouldBeEqualTo(PyStringNode $content): void
    {
        $expected = json_encode(json_decode((string) $content), JSON_PRETTY_PRINT);

        $response = $this->client->getResponse()->getContent();

        Assert::assertNotFalse($response);

        $jsonResponse = json_decode($response, true);

        Assert::assertNotFalse($jsonResponse);

        $actual = json_encode($jsonResponse, JSON_PRETTY_PRINT);

        Assert::assertEquals($expected, $actual);
    }

    #[Then('I see a JSON that should be similar to:')]
    public function iSeeAJsonThatShouldBeSimilarTo(PyStringNode $content): void
    {
        $expected = json_decode((string) $content);
        $response = $this->client->getResponse()->getContent();

        Assert::assertNotFalse($response);

        $actual = json_decode($response, true);

        foreach ($expected as $attr => $value) {
            Assert::assertTrue(isset($actual->$attr) || isset($actual[$attr]));
        }
    }

    #[Then('I see a text :text')]
    public function iSeeAText(string $text): void
    {
        $response = $this->client->getResponse()->getContent();

        Assert::assertNotFalse($response);

        Assert::assertStringContainsString($text, $response);
    }
}
