<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Library;

use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaFilter;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Domain\Model\Media\MediaStorage;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class LibraryContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(
        KernelBrowser $kernelBrowser,
    ) {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    #[AfterScenario]
    public function after(AfterScenarioScope $scope): void
    {
        $this->client->reset();
    }

    private function getMediaRepository(): MediaRepository
    {
        return $this->client->getContainer()->get(MediaRepository::class);
    }

    private function getMediaStorage(): MediaStorage
    {
        return $this->client->getContainer()->get(MediaStorage::class);
    }

    #[Given('media :name with id :id exists')]
    public function mediaWithIdExists(string $name, string $id): void
    {
        $this->addMedia(MediaMother::fromNameAndId($name, $id));
    }

    #[Given('document :name with id :id exists')]
    public function documentWithIdExists(string $name, string $id): void
    {
        $this->addMedia(MediaMother::from(new MediaId($id), $name, 'application/pdf'));
    }

    #[Given('media :name of account :account exists')]
    public function mediaOfAccountExists(string $name, string $account): void
    {
        $this->addMedia(MediaMother::fromNameAndAccount($name, new AccountId($account)));
    }

    #[Given('media :name with id :id of account :account exists')]
    public function mediaWithIdOfAccountExists(string $name, string $id, string $account): void
    {
        $this->addMedia(MediaMother::from(new MediaId($id), $name, 'image/png', new AccountId($account)));
    }

    #[When('I submit the upload form with file :fileName')]
    public function iSubmitTheUploadFormWithFile(string $fileName): void
    {
        $crawler = $this->client->getCrawler();

        $form = $crawler->selectButton('Subir')->form();

        $field = $form['upload_media[file]'];

        assert($field instanceof FileFormField);

        $field->upload($this->createLocalFile($fileName));

        $this->client->submit($form);
    }

    #[When('I upload the file :fileName through the media picker of :path')]
    public function iUploadTheFileThroughTheMediaPickerOf(string $fileName, string $path): void
    {
        $this->uploadThroughThePicker($fileName, $this->pickerCsrfToken($path));
    }

    #[When('I upload the file :fileName through the media picker with the token :token')]
    public function iUploadTheFileThroughTheMediaPickerWithTheToken(string $fileName, string $token): void
    {
        $this->uploadThroughThePicker($fileName, $token);
    }

    #[Then('the library contains :total files')]
    public function theLibraryContainsFiles(int $total): void
    {
        Assert::assertCount($total, $this->getMediaRepository()->findByFilter(new MediaFilter(limit: 0)));
    }

    private function uploadThroughThePicker(string $fileName, string $token): void
    {
        $this->client->requestWithFiles(
            'POST',
            '/es/library/media/ajax-upload',
            ['_token' => $token],
            ['file' => new UploadedFile($this->createLocalFile($fileName), $fileName, 'image/png', null, true)],
        );
    }

    /**
     * Reads the token exactly where the browser reads it: the picker widget
     * rendered in the page holding it.
     */
    private function pickerCsrfToken(string $path): string
    {
        $this->client->request('GET', $path);

        $token = $this->client->getCrawler()->filter('[data-media-picker-token-value]')->attr('data-media-picker-token-value');

        Assert::assertNotNull($token);

        return $token;
    }

    private function addMedia(Media $media): void
    {
        $this->getMediaStorage()->store(
            $media->id(),
            null,
            $media->extension(),
            $this->createLocalFile($media->fileName()),
        );

        $this->getMediaRepository()->add($media);
    }

    private function createLocalFile(string $fileName): string
    {
        $path = sprintf('%s/%s', sys_get_temp_dir(), uniqid('behat-media-', true));

        file_put_contents($path, sprintf('contents of %s', $fileName));

        return $path;
    }
}
