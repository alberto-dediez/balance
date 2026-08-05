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
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * This context class contains the definitions of the steps used by the demo
 * feature file. Learn how to get started with Behat and BDD on Behat's website.
 *
 * @see http://behat.org/en/latest/quick_start.html
 */
final class WebContext implements Context
{
    private readonly AcceptanceClient $client;

    /** @var array<string, string> */
    private array $files = [];

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

    #[When('I make a POST request to :path')]
    public function iMakeAPostRequestTo(string $path): void
    {
        $this->client->request('POST', $path);
    }

    #[When('I click on link :text')]
    public function iClickOnLink(string $text): void
    {
        $crawler = $this->client->getCrawler();

        $link = $crawler->selectLink($text)->link();

        $this->client->click($link);
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
        $formName = $formData->getNode()->getAttribute('name');

        $arguments = explode('|', $data);

        foreach ($arguments as $argument) {
            if ('' === $argument) {
                continue;
            }

            [$name, $value] = explode(':', $argument);

            $key = '[' == $name[0] ? sprintf('%s%s', $formName, $name) : sprintf('%s[%s]', $formName, $name);

            if ('' !== $value && '[' == $value[0]) {
                $value = explode(',', substr($value, 1, -1));
            }

            $compoundField = $formData[$key];
            try {
                $formData[$key] = $value;
            } catch (\InvalidArgumentException $e) {
                if (!str_contains($e->getMessage(), 'compound field')) {
                    throw $e;
                }
                $values = is_array($value) ? $value : [$value];
                array_walk_recursive($compoundField, static function (mixed $field) use ($values): void {
                    if ($field instanceof ChoiceFormField && [] !== array_intersect($field->availableOptionValues(), $values)) {
                        $field->tick();
                    }
                });
            }
        }

        foreach ($this->files as $field => $path) {
            $key = '[' === $field[0] ? sprintf('%s%s', $formName, $field) : sprintf('%s[%s]', $formName, $field);

            $fileField = $formData[$key];
            assert($fileField instanceof FileFormField);

            $fileField->upload($path);
        }

        $this->client->submit($formData);

        $this->files = [];
    }

    /** Reproduces what the autocomplete controller does in the browser: injecting the chosen option into the select. */
    #[When('I pick :value in the autocomplete :field')]
    public function iPickInTheAutocomplete(string $value, string $field): void
    {
        $select = $this->client->getCrawler()->filter(sprintf('select[name$="[%s]"]', $field))->getNode(0);

        Assert::assertNotNull($select);
        Assert::assertNotNull($select->ownerDocument);

        $option = $select->ownerDocument->createElement('option', $value);
        $option->setAttribute('value', $value);

        $select->appendChild($option);
    }

    #[When('I attach the file :path to :field')]
    public function iAttachTheFileTo(string $path, string $field): void
    {
        $this->files[$field] = $path;
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

    #[Then('I am redirected to route :name')]
    public function iAmRedirectedToRoute(string $name): void
    {
        $this->client->followRedirect();

        Assert::assertEquals($name, $this->client->getRequest()->attributes->get('_route'));
    }

    #[Then('I see a form')]
    public function iSeeAForm(): void
    {
        $crawler = $this->client->getCrawler();

        $forms = $crawler->filter('form');

        Assert::assertCount(1, $forms);
    }

    #[Then('I see a form field :name')]
    public function iSeeAFormField(string $name): void
    {
        $crawler = $this->client->getCrawler();

        $field = $crawler->filter(sprintf('[name*="%s"]', $name));

        Assert::assertGreaterThan(0, $field->count(), sprintf('Form field "%s" not found in the page', $name));
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

    #[Then('I do not see a text :text')]
    public function iDoNotSeeAText(string $text): void
    {
        $response = $this->client->getResponse()->getContent();

        Assert::assertNotFalse($response);

        Assert::assertStringNotContainsString($text, $response);
    }
}
