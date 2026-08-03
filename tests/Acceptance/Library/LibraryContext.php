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

        $fileFields = $form['upload_media[file]'];

        assert(is_array($fileFields));

        $field = $fileFields[0];

        assert($field instanceof FileFormField);

        $field->upload($this->createLocalFile($fileName));

        $this->client->submit($form);
    }

    #[When('I submit the upload form with files :fileNames')]
    public function iSubmitTheUploadFormWithFiles(string $fileNames): void
    {
        $form = $this->client->getCrawler()->selectButton('Subir')->form();

        $files = array_map(
            fn(string $fileName): UploadedFile => new UploadedFile($this->createLocalFile(trim($fileName)), trim($fileName), null, null, true),
            explode(',', $fileNames),
        );

        $fileValues = $form->getPhpFiles();
        $fileValues['upload_media']['file'] = $files;

        $this->client->requestWithFiles($form->getMethod(), $form->getUri(), $form->getPhpValues(), $fileValues);
    }

    #[When('I upload the file :fileName through the media picker of :path')]
    public function iUploadTheFileThroughTheMediaPickerOf(string $fileName, string $path): void
    {
        $this->client->request('GET', $path);

        $this->uploadThroughThePicker($fileName, $this->pickerAttribute('data-media-picker-token-value'), $this->pickerAttribute('data-media-picker-types-value'));
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

    private function uploadThroughThePicker(string $fileName, string $token, string $types = ''): void
    {
        $this->client->requestWithFiles(
            'POST',
            '/es/library/media/ajax-upload',
            ['_token' => $token, 'types' => $types],
            ['file' => new UploadedFile($this->createLocalFile($fileName), $fileName, null, null, true)],
        );
    }

    /**
     * Reads the attribute exactly where the browser reads it: the picker
     * widget rendered in the page holding it.
     */
    private function pickerAttribute(string $attribute): string
    {
        $value = $this->client->getCrawler()->filter('[data-media-picker-token-value]')->attr($attribute);

        Assert::assertNotNull($value);

        return $value;
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

    /**
     * The uploaded content must match the extension for real, since the mime
     * type validation reads it from the actual file content rather than
     * trusting the file name.
     */
    private function createLocalFile(string $fileName): string
    {
        $path = sprintf('%s/%s', sys_get_temp_dir(), uniqid('behat-media-', true));

        file_put_contents($path, match (pathinfo($fileName, PATHINFO_EXTENSION)) {
            'png' => base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
            'pdf' => "%PDF-1.4\n%behat\n",
            default => sprintf('contents of %s', $fileName),
        });

        return $path;
    }
}
