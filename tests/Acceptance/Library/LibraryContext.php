<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Library;

use App\Library\Domain\Model\Backup\MediaBackup;
use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderFilter;
use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Folder\FolderRepository;
use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaFilter;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Domain\Model\Media\MediaStorage;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaBackupRepository;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryStorageSyncTaskRepository;
use App\Library\Infrastructure\Storage\InMemoryMediaStorage;
use App\Library\Infrastructure\Storage\MediaStorageRegistry;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class LibraryContext implements Context
{
    private AcceptanceClient $client;

    private ?string $lastRemovedMediaPath = null;

    /** @var array<string, string> */
    private array $backupPaths = [];

    /** @var list<string> */
    private array $extractedFiles = [];

    public function __construct(
        KernelBrowser $kernelBrowser,
    ) {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    #[AfterScenario]
    public function after(AfterScenarioScope $scope): void
    {
        $this->client->reset();
        $this->lastRemovedMediaPath = null;
        $this->backupPaths = [];

        foreach ($this->extractedFiles as $file) {
            @unlink($file);
        }

        $this->extractedFiles = [];
    }

    private function getMediaRepository(): MediaRepository
    {
        return $this->client->getContainer()->get(MediaRepository::class);
    }

    private function getFolderRepository(): FolderRepository
    {
        return $this->client->getContainer()->get(FolderRepository::class);
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

    #[Given('media :name with id :id and visibility :visibility exists')]
    public function mediaWithVisibilityExists(string $name, string $id, string $visibility): void
    {
        $this->addMedia(MediaMother::from(new MediaId($id), $name, 'image/png', null, null, MediaVisibility::from($visibility)));
    }

    #[Given('media :name with id :id and visibility :visibility of account :account exists')]
    public function mediaWithVisibilityOfAccountExists(string $name, string $id, string $visibility, string $account): void
    {
        $this->addMedia(MediaMother::from(new MediaId($id), $name, 'image/png', new AccountId($account), null, MediaVisibility::from($visibility)));
    }

    #[Given('media :name with id :id shared in the library of account :account exists')]
    public function mediaSharedInTheLibraryOfAccountExists(string $name, string $id, string $account): void
    {
        $this->addMedia(MediaMother::from(new MediaId($id), $name, 'image/png', new AccountId($account), null, MediaVisibility::System, true));
    }

    #[Given('folder :name with id :id exists')]
    public function folderWithIdExists(string $name, string $id): void
    {
        $this->getFolderRepository()->add(FolderMother::fromNameAndId($name, $id));
    }

    #[Given('folder :name with id :id of account :account exists')]
    public function folderWithIdOfAccountExists(string $name, string $id, string $account): void
    {
        $this->getFolderRepository()->add(FolderMother::from(new FolderId($id), $name, new AccountId($account)));
    }

    #[Given('subfolder :name with id :id in folder :parent exists')]
    public function subfolderWithIdInFolderExists(string $name, string $id, string $parent): void
    {
        $this->getFolderRepository()->add(FolderMother::from(new FolderId($id), $name, null, new FolderId($parent)));
    }

    #[Given('subfolder :name with id :id in folder :parent of account :account exists')]
    public function subfolderWithIdInFolderOfAccountExists(string $name, string $id, string $parent, string $account): void
    {
        $this->getFolderRepository()->add(FolderMother::from(new FolderId($id), $name, new AccountId($account), new FolderId($parent)));
    }

    #[Given('media :name with id :id in folder :folder exists')]
    public function mediaWithIdInFolderExists(string $name, string $id, string $folder): void
    {
        $media = MediaMother::fromNameAndId($name, $id);
        $media->moveToFolder(new FolderId($folder));

        $this->addMedia($media);
    }

    #[Given('media :name with id :id in folder :folder of account :account exists')]
    public function mediaWithIdInFolderOfAccountExists(string $name, string $id, string $folder, string $account): void
    {
        $media = MediaMother::from(new MediaId($id), $name, 'image/png', new AccountId($account));
        $media->moveToFolder(new FolderId($folder));

        $this->addMedia($media);
    }

    #[Given('media :name with id :id shared in the library of account :account in folder :folder exists')]
    public function mediaSharedInTheLibraryOfAccountInFolderExists(string $name, string $id, string $account, string $folder): void
    {
        $media = MediaMother::from(new MediaId($id), $name, 'image/png', new AccountId($account), null, MediaVisibility::System, true);
        $media->moveToFolder(new FolderId($folder));

        $this->addMedia($media);
    }

    #[When('I filter the library by the folder :name')]
    public function iFilterTheLibraryByTheFolder(string $name): void
    {
        $this->filterTheLibraryByFolder(fn(ChoiceFormField $field): string => $this->optionValue('select[name$="[folder]"]', $name));
    }

    #[When('I filter the library by all the folders')]
    public function iFilterTheLibraryByAllTheFolders(): void
    {
        $this->filterTheLibraryByFolder(static fn(ChoiceFormField $field): string => 'all');
    }

    #[When('I filter the library by the subfolder :path')]
    public function iFilterTheLibraryByTheSubfolder(string $path): void
    {
        $this->filterTheLibraryByFolder(fn(ChoiceFormField $field): string => $this->optionValue('select[name$="[folder]"]', $path));
    }

    #[When('I filter the library by all the subfolders of :folder')]
    public function iFilterTheLibraryByAllTheSubfoldersOf(string $folder): void
    {
        $this->filterTheLibraryByFolder(fn(ChoiceFormField $field): string => 'all:' . $this->optionValue('select[name$="[folder]"]', $folder));
    }

    #[When('I move the media :ids to the folder :name')]
    public function iMoveTheMediaToTheFolder(string $ids, string $name): void
    {
        $this->moveTheMedia($ids, $this->optionValue('select[name$="[folderId]"]', $name));
    }

    #[When('I move the media :ids out of their folder')]
    public function iMoveTheMediaOutOfTheirFolder(string $ids): void
    {
        $this->moveTheMedia($ids, '');
    }

    #[Then('the active filters show :text')]
    public function theActiveFiltersShow(string $text): void
    {
        $filters = $this->client->getCrawler()->filter('.filter-show')->each(
            static fn(Crawler $node): string => (string) preg_replace('/\s+/', ' ', trim($node->text())),
        );

        Assert::assertContains($text, $filters);
    }

    #[Then('I see the folder tiles :labels')]
    public function iSeeTheFolderTiles(string $labels): void
    {
        $this->assertTiles('folders', $labels, fn(string $label): string => $this->folderTileValue($label));
    }

    #[Then('the folder tile :label is active')]
    public function theFolderTileIsActive(string $label): void
    {
        $this->assertActiveTile('folders', $label);
    }

    #[Then('I see the subfolder tiles :labels of the folder :folder')]
    public function iSeeTheSubfolderTilesOfTheFolder(string $labels, string $folder): void
    {
        $this->assertTiles('subfolders', $labels, fn(string $label): string => $this->subfolderTileValue($folder, $label));
    }

    #[Then('the subfolder tile :label is active')]
    public function theSubfolderTileIsActive(string $label): void
    {
        $this->assertActiveTile('subfolders', $label);
    }

    #[Then('the media :id is removed')]
    public function theMediaIsRemoved(string $id): void
    {
        Assert::assertNull($this->getMediaRepository()->find(new MediaId($id)));
    }

    #[Then('the media :id is not removed')]
    public function theMediaIsNotRemoved(string $id): void
    {
        Assert::assertNull($this->getMedia($id)->removedAt());
    }

    #[Then('the media :id is in the folder :folder')]
    public function theMediaIsInTheFolder(string $id, string $folder): void
    {
        $folderId = $this->getMedia($id)->folderId();

        Assert::assertNotNull($folderId);
        Assert::assertTrue($folderId->equals(new FolderId($folder)));
    }

    #[Then('the media :id is not in any folder')]
    public function theMediaIsNotInAnyFolder(string $id): void
    {
        Assert::assertNull($this->getMedia($id)->folderId());
    }

    #[Then('the folder :name exists')]
    public function theFolderExists(string $name): void
    {
        Assert::assertContains($name, $this->folderNames());
    }

    #[Then('the folder :name does not exist')]
    public function theFolderDoesNotExist(string $name): void
    {
        Assert::assertNotContains($name, $this->folderNames());
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

    #[When('I submit the upload form with files :fileNames into the folder :name')]
    public function iSubmitTheUploadFormWithFilesIntoTheFolder(string $fileNames, string $name): void
    {
        $folderId = $this->optionValue('select[name$="[folderId]"]', $name);

        $form = $this->client->getCrawler()->selectButton('Subir')->form();

        $files = array_map(
            fn(string $fileName): UploadedFile => new UploadedFile($this->createLocalFile(trim($fileName)), trim($fileName), null, null, true),
            explode(',', $fileNames),
        );

        $values = $form->getPhpValues();
        $values['upload_media']['folderId'] = $folderId;

        $fileValues = $form->getPhpFiles();
        $fileValues['upload_media']['file'] = $files;

        $this->client->requestWithFiles($form->getMethod(), $form->getUri(), $values, $fileValues);
    }

    #[Then('the upload form has the folder :name selected')]
    public function theUploadFormHasTheFolderSelected(string $name): void
    {
        Assert::assertSame([$name], $this->selectedUploadFolders());
    }

    #[Then('the upload form has no folder selected')]
    public function theUploadFormHasNoFolderSelected(): void
    {
        Assert::assertSame([], $this->selectedUploadFolders());
    }

    #[When('I send the media :id to the folder :folder through a forged edit form')]
    public function iSendTheMediaToTheFolderThroughAForgedEditForm(string $id, string $folder): void
    {
        $form = $this->client->getCrawler()->selectButton('Guardar y salir')->form();

        $formName = (string) $form->getFormNode()->getAttribute('name');

        $values = $form->getPhpValues();
        $values[$formName]['folderId'] = $folder;

        $this->client->requestWithFiles($form->getMethod(), $form->getUri(), $values);
    }

    #[Then('every file of the library is in the folder :folder')]
    public function everyFileOfTheLibraryIsInTheFolder(string $folder): void
    {
        $media = $this->getMediaRepository()->findByFilter(new MediaFilter(limit: 0));

        Assert::assertNotEmpty($media);

        foreach ($media as $element) {
            Assert::assertNotNull($element->folderId());
            Assert::assertTrue($element->folderId()->equals(new FolderId($folder)));
        }
    }

    #[When('I upload the file :fileName through the media picker of :path')]
    public function iUploadTheFileThroughTheMediaPickerOf(string $fileName, string $path): void
    {
        $this->client->request('GET', $path);

        $this->uploadThroughThePicker(
            $fileName,
            $this->pickerAttribute('data-media-picker-token-value'),
            $this->pickerAttribute('data-media-picker-types-value'),
            $this->pickerAttribute('data-media-picker-visibility-value'),
        );
    }

    #[When('I upload the file :fileName through the media picker of :path asking for the visibility :visibility')]
    public function iUploadTheFileThroughTheMediaPickerAskingForTheVisibility(string $fileName, string $path, string $visibility): void
    {
        $this->client->request('GET', $path);

        $this->uploadThroughThePicker(
            $fileName,
            $this->pickerAttribute('data-media-picker-token-value'),
            $this->pickerAttribute('data-media-picker-types-value'),
            $visibility,
        );
    }

    #[When('I upload the file :fileName through the media picker with the token :token')]
    public function iUploadTheFileThroughTheMediaPickerWithTheToken(string $fileName, string $token): void
    {
        $this->uploadThroughThePicker($fileName, $token);
    }

    #[When('I filter the library including the shared media')]
    public function iFilterTheLibraryIncludingTheSharedMedia(): void
    {
        $crawler = $this->client->getCrawler();

        $form = $crawler->filter('[name$="[filter]"]')->form();

        $field = $form[(string) $crawler->filter('input[name$="[includeShared]"]')->attr('name')];

        assert($field instanceof ChoiceFormField);

        $field->tick();

        $this->client->submit($form);
    }

    #[Then('the file :fileName has the visibility :visibility')]
    public function theFileHasTheVisibility(string $fileName, string $visibility): void
    {
        $media = $this->getMediaRepository()->findByFilter(new MediaFilter(name: $fileName, limit: 0));

        Assert::assertCount(1, $media);
        Assert::assertEquals(MediaVisibility::from($visibility), $media[0]->visibility());
    }

    #[Then('the file :fileName is shared in the library')]
    public function theFileIsSharedInTheLibrary(string $fileName): void
    {
        $media = $this->getMediaRepository()->findByFilter(new MediaFilter(name: $fileName, limit: 0));

        Assert::assertCount(1, $media);
        Assert::assertTrue($media[0]->isShared());
    }

    #[Then('the file :fileName is not shared in the library')]
    public function theFileIsNotSharedInTheLibrary(string $fileName): void
    {
        $media = $this->getMediaRepository()->findByFilter(new MediaFilter(name: $fileName, limit: 0));

        Assert::assertCount(1, $media);
        Assert::assertFalse($media[0]->isShared());
    }

    #[Then('the library contains :total files')]
    public function theLibraryContainsFiles(int $total): void
    {
        Assert::assertCount($total, $this->getMediaRepository()->findByFilter(new MediaFilter(limit: 0)));
    }

    #[Then('media :id is stored in the :driver storage')]
    public function mediaIsStoredInTheStorage(string $id, string $driver): void
    {
        Assert::assertTrue($this->getStorage($driver)->exists($this->getMedia($id)->path()));
    }

    #[Then('media :id is not stored in the :driver storage')]
    public function mediaIsNotStoredInTheStorage(string $id, string $driver): void
    {
        Assert::assertFalse($this->getStorage($driver)->exists($this->getMedia($id)->path()));
    }

    #[Given('the file of media :id is missing in the :driver storage')]
    public function theFileOfMediaIsMissingInTheStorage(string $id, string $driver): void
    {
        $this->getStorage($driver)->delete($this->getMedia($id)->path());
    }

    #[Given('the main media storage is unavailable')]
    public function theMainMediaStorageIsUnavailable(): void
    {
        $this->getMainStorage()->markUnavailable();
    }

    #[Given('the main media storage is available again')]
    public function theMainMediaStorageIsAvailableAgain(): void
    {
        $this->getMainStorage()->markAvailable();
    }

    #[Then('media :id is stored in the main storage')]
    public function mediaIsStoredInTheMainStorage(string $id): void
    {
        Assert::assertTrue($this->getMainStorage()->exists($this->getMedia($id)->path()));
    }

    #[Then('media :id is not stored in the main storage')]
    public function mediaIsNotStoredInTheMainStorage(string $id): void
    {
        Assert::assertFalse($this->getMainStorage()->exists($this->getMedia($id)->path()));
    }

    #[Then('media :id is stored in the fallback storage')]
    public function mediaIsStoredInTheFallbackStorage(string $id): void
    {
        Assert::assertTrue($this->getFallbackStorage()->exists($this->getMedia($id)->path()));
    }

    #[Then('media :id is not stored in the fallback storage')]
    public function mediaIsNotStoredInTheFallbackStorage(string $id): void
    {
        Assert::assertFalse($this->getFallbackStorage()->exists($this->getMedia($id)->path()));
    }

    #[Then('the last uploaded media is stored in the :storage storage')]
    public function theLastUploadedMediaIsStoredInTheStorage(string $storage): void
    {
        Assert::assertTrue($this->getStorageByRole($storage)->exists($this->getLastUploadedMedia()->path()));
    }

    #[Then('the last uploaded media is not stored in the :storage storage')]
    public function theLastUploadedMediaIsNotStoredInTheStorage(string $storage): void
    {
        Assert::assertFalse($this->getStorageByRole($storage)->exists($this->getLastUploadedMedia()->path()));
    }

    #[When('I go to the show page of the last uploaded media')]
    public function iGoToTheShowPageOfTheLastUploadedMedia(): void
    {
        $this->client->request('GET', sprintf('/es/library/media/%s/show', (string) $this->getLastUploadedMedia()->id()));
    }

    #[When('I go to the remove page of the last uploaded media')]
    public function iGoToTheRemovePageOfTheLastUploadedMedia(): void
    {
        $media = $this->getLastUploadedMedia();
        $this->lastRemovedMediaPath = $media->path();

        $token = $this->client->getContainer()->get(CsrfTokenManagerInterface::class)->getToken('remove')->getValue();

        $this->client->request(
            'GET',
            sprintf('/es/library/media/remove/%s?_token=%s', (string) $media->id(), urlencode($token)),
            ['HTTP_SEC_FETCH_SITE' => 'same-origin'],
        );
    }

    #[Then('the last removed media is not stored in the fallback storage')]
    public function theLastRemovedMediaIsNotStoredInTheFallbackStorage(): void
    {
        Assert::assertNotNull($this->lastRemovedMediaPath);
        Assert::assertFalse($this->getFallbackStorage()->exists($this->lastRemovedMediaPath));
    }

    #[Then('there are :count pending storage sync tasks')]
    public function thereArePendingStorageSyncTasks(int $count): void
    {
        $tasks = $this->client->getContainer()->get(InMemoryStorageSyncTaskRepository::class);

        Assert::assertInstanceOf(InMemoryStorageSyncTaskRepository::class, $tasks);
        Assert::assertSame($count, $tasks->countPending());
    }

    #[Then('the backup storage contains media :id')]
    public function mediaIsStoredInTheBackupStorage(string $id): void
    {
        Assert::assertTrue($this->getBackupStorage()->exists($this->getBackupPath($id)));
    }

    #[Then('the backup storage does not contain media :id')]
    public function mediaIsNotStoredInTheBackupStorage(string $id): void
    {
        Assert::assertFalse($this->getBackupStorage()->exists($this->getBackupPath($id)));
    }

    #[Given('the backup media storage is unavailable')]
    public function theBackupMediaStorageIsUnavailable(): void
    {
        $this->getBackupStorage()->markUnavailable();
    }

    #[Given('the backup of media :id was removed :days days ago')]
    public function theBackupOfMediaWasRemovedDaysAgo(string $id, int $days): void
    {
        $backups = $this->getMediaBackupRepository();
        $backup = $backups->findByMediaId($id);

        Assert::assertNotNull($backup);

        $this->backupPaths[$id] = $backup->path();

        $backups->save(new MediaBackup(
            $backup->path(),
            $backup->mediaId(),
            $backup->backedUpAt(),
            new \DateTimeImmutable(sprintf('-%d days', $days)),
        ));
    }

    #[Then('the backup of media :id is marked as removed')]
    public function theBackupOfMediaIsMarkedAsRemoved(string $id): void
    {
        $backup = $this->getMediaBackupRepository()->findByMediaId($id);

        Assert::assertNotNull($backup);
        Assert::assertTrue($backup->isRemoved());
    }

    #[Then('the file :path exists with the contents of media :id')]
    public function theFileExistsWithTheContentsOfMedia(string $path, string $id): void
    {
        $this->extractedFiles[] = $path;

        Assert::assertFileExists($path);

        $stream = $this->getBackupStorage()->read($this->getBackupPath($id));
        $contents = (string) stream_get_contents($stream);
        fclose($stream);

        Assert::assertSame($contents, file_get_contents($path));
    }

    /**
     * @param \Closure(ChoiceFormField): string $value
     */
    private function filterTheLibraryByFolder(\Closure $value): void
    {
        $crawler = $this->client->getCrawler();

        $form = $crawler->filter('[name$="[filter]"]')->form();

        $field = $form[(string) $crawler->filter('select[name$="[folder]"]')->attr('name')];

        assert($field instanceof ChoiceFormField);

        $field->select($value($field));

        $this->client->submit($form);
    }

    private function moveTheMedia(string $ids, string $folderId): void
    {
        $form = $this->client->getCrawler()->selectButton('Realizar')->form();

        $formName = (string) $form->getFormNode()->getAttribute('name');

        $values = $form->getPhpValues();

        $values[$formName]['elements'] = array_map('trim', explode(',', $ids));
        $values[$formName]['action'] = 'move';
        $values[$formName]['folderId'] = $folderId;

        $this->client->requestWithFiles($form->getMethod(), $form->getUri(), $values);
    }

    /**
     * @param \Closure(string): string $expectedValue
     */
    private function assertTiles(string $strip, string $labels, \Closure $expectedValue): void
    {
        $expected = array_map(trim(...), explode(',', $labels));

        $tiles = $this->client->getCrawler()->filter(sprintf('[data-folder-strip="%s"] .folder-tile__open', $strip));

        Assert::assertSame($expected, $tiles->each(static fn(Crawler $tile): string => trim($tile->text())));

        $folderField = $this->client->getCrawler()->filter('select[name$="[folder]"]');

        Assert::assertGreaterThan(0, $folderField->count(), 'There is no folder filter field');

        $tiles->each(function (Crawler $tile) use ($folderField, $expectedValue): void {
            $label = trim($tile->text());

            Assert::assertSame('#' . $folderField->attr('id'), $tile->attr('data-filter-field-param'), sprintf('The folder tile "%s" does not point to the folder filter', $label));
            Assert::assertSame($expectedValue($label), $tile->attr('data-filter-value-param'), sprintf('The folder tile "%s" has a wrong value', $label));
        });
    }

    private function assertActiveTile(string $strip, string $label): void
    {
        $crawler = $this->client->getCrawler();

        $active = $crawler->filter(sprintf('[data-folder-strip="%s"] .folder-tile__open[aria-current="true"]', $strip))->each(
            static fn(Crawler $tile): string => trim($tile->text()),
        );

        Assert::assertSame([$label], $active);

        $highlighted = $crawler->filter(sprintf('[data-folder-strip="%s"] .folder-tile--active .folder-tile__open', $strip))->each(
            static fn(Crawler $tile): string => trim($tile->text()),
        );

        Assert::assertSame([$label], $highlighted);
    }

    private function subfolderTileValue(string $folder, string $label): string
    {
        $folderId = $this->optionValue('select[name$="[folder]"]', $folder);

        return match ($label) {
            'Sin subcarpeta' => $folderId,
            'Todas las subcarpetas' => 'all:' . $folderId,
            default => $this->optionValue('select[name$="[folder]"]', $folder . ' / ' . $label),
        };
    }

    private function folderTileValue(string $label): string
    {
        return match ($label) {
            'Sin carpeta' => '',
            'Todas las carpetas' => 'all',
            default => $this->optionValue('select[name$="[folder]"]', $label),
        };
    }

    private function optionValue(string $selector, string $label): string
    {
        $options = $this->client->getCrawler()->filter($selector . ' option')->reduce(
            static fn($option): bool => trim($option->text()) === $label,
        );

        Assert::assertGreaterThan(0, $options->count(), sprintf('There is no option "%s"', $label));

        return (string) $options->first()->attr('value');
    }

    /**
     * @return list<string>
     */
    private function selectedUploadFolders(): array
    {
        $crawler = $this->client->getCrawler();

        Assert::assertGreaterThan(0, $crawler->filter('select[name$="[folderId]"]')->count(), 'There is no folder field');

        return $crawler->filter('select[name$="[folderId]"] option[selected]')
            ->reduce(static fn(Crawler $option): bool => '' !== (string) $option->attr('value'))
            ->each(static fn(Crawler $option): string => trim($option->text()));
    }

    /**
     * @return list<string>
     */
    private function folderNames(): array
    {
        return array_map(
            static fn(Folder $folder): string => $folder->name(),
            $this->getFolderRepository()->findByFilter(new FolderFilter(limit: 0)),
        );
    }

    private function getStorage(string $driver): MediaStorage
    {
        return $this->client->getContainer()->get(MediaStorageRegistry::class)->get($driver);
    }

    private function getMainStorage(): InMemoryMediaStorage
    {
        return $this->getInMemoryStorage('library.media_storage.primary');
    }

    private function getFallbackStorage(): InMemoryMediaStorage
    {
        return $this->getInMemoryStorage('library.media_storage.fallback_in_memory');
    }

    private function getInMemoryStorage(string $serviceId): InMemoryMediaStorage
    {
        $storage = $this->client->getContainer()->get($serviceId);

        Assert::assertInstanceOf(InMemoryMediaStorage::class, $storage);

        return $storage;
    }

    private function getBackupStorage(): InMemoryMediaStorage
    {
        return $this->getInMemoryStorage('library.media_storage.backup_in_memory');
    }

    private function getMediaBackupRepository(): InMemoryMediaBackupRepository
    {
        $backups = $this->client->getContainer()->get(InMemoryMediaBackupRepository::class);

        Assert::assertInstanceOf(InMemoryMediaBackupRepository::class, $backups);

        return $backups;
    }

    private function getBackupPath(string $id): string
    {
        if (isset($this->backupPaths[$id])) {
            return $this->backupPaths[$id];
        }

        $media = $this->getMediaRepository()->find(new MediaId($id));
        $path = $media?->path() ?? $this->getMediaBackupRepository()->findByMediaId($id)?->path();

        Assert::assertNotNull($path);

        return $this->backupPaths[$id] = $path;
    }

    private function getStorageByRole(string $storage): InMemoryMediaStorage
    {
        return match ($storage) {
            'main' => $this->getMainStorage(),
            'fallback' => $this->getFallbackStorage(),
            default => throw new \InvalidArgumentException(sprintf('Unknown storage "%s"', $storage)),
        };
    }

    private function getLastUploadedMedia(): Media
    {
        $media = $this->getMediaRepository()->findByFilter(new MediaFilter(limit: 1));

        Assert::assertCount(1, $media);

        return $media[0];
    }

    private function getMedia(string $id): Media
    {
        $media = $this->getMediaRepository()->find(new MediaId($id));

        Assert::assertNotNull($media);

        return $media;
    }

    private function uploadThroughThePicker(string $fileName, string $token, string $types = '', string $visibility = ''): void
    {
        $this->client->requestWithFiles(
            'POST',
            '/es/library/media/ajax-upload',
            ['_token' => $token, 'types' => $types, 'visibility' => $visibility],
            ['file' => new UploadedFile($this->createLocalFile($fileName), $fileName, null, null, true)],
        );
    }

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
