<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Changelog;

use App\Changelog\Application\Announcement\PublishScheduled\PublishScheduledAnnouncementsRequest;
use App\Changelog\Application\Announcement\PublishScheduled\PublishScheduledAnnouncementsService;
use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\YouTubeVideo;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;
use App\Changelog\Infrastructure\Persistence\InMemory\InMemoryChangelogEntryRepository;
use App\Changelog\Infrastructure\Persistence\InMemory\Repository\InMemoryAnnouncementRepository;
use App\Changelog\Infrastructure\Storage\InMemoryAnnouncementManualStorage;
use App\Changelog\Infrastructure\Storage\InMemoryManualStorage;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Messenger\DeferredCommandMessenger;
use App\Shared\Infrastructure\Messenger\DeferredLowMessage;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementMother;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Field\FormField;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class ChangelogContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(KernelBrowser $kernelBrowser)
    {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    #[AfterScenario]
    public function after(AfterScenarioScope $scope): void
    {
        $this->getChangelogEntryRepository()->clear();
        $this->getManualStorage()->clear();
        $this->getAnnouncementRepository()->clear();
        $this->getAnnouncementManualStorage()->clear();
        $this->getLowPriorityTransport()->reset();
        $this->client->reset();
    }

    private function getChangelogEntryRepository(): InMemoryChangelogEntryRepository
    {
        return $this->client->getContainer()->get(InMemoryChangelogEntryRepository::class);
    }

    private function getManualStorage(): InMemoryManualStorage
    {
        return $this->client->getContainer()->get(InMemoryManualStorage::class);
    }

    private function getAnnouncementRepository(): InMemoryAnnouncementRepository
    {
        return $this->client->getContainer()->get(InMemoryAnnouncementRepository::class);
    }

    private function getAnnouncementManualStorage(): InMemoryAnnouncementManualStorage
    {
        return $this->client->getContainer()->get(InMemoryAnnouncementManualStorage::class);
    }

    private function getLowPriorityTransport(): InMemoryTransport
    {
        $transport = $this->client->getContainer()->get('messenger.transport.async_low');

        Assert::assertInstanceOf(InMemoryTransport::class, $transport);

        return $transport;
    }

    private function getAnnouncement(string $id): Announcement
    {
        $announcement = $this->getAnnouncementRepository()->find(new AnnouncementId($id));

        Assert::assertInstanceOf(Announcement::class, $announcement, sprintf('Announcement "%s" not found', $id));

        return $announcement;
    }

    #[Given('announcement :title with id :id exists')]
    public function announcementExists(string $title, string $id): void
    {
        $this->getAnnouncementRepository()->add(AnnouncementMother::fromTitleAndId($title, $id));
    }

    #[Given('published announcement :title with id :id exists')]
    public function publishedAnnouncementExists(string $title, string $id): void
    {
        $this->getAnnouncementRepository()->add(AnnouncementMother::published($title, new AnnouncementId($id)));
    }

    #[Given('announcement :title with id :id is scheduled for :datetime')]
    public function announcementIsScheduledFor(string $title, string $id, string $datetime): void
    {
        $this->getAnnouncementRepository()->add(AnnouncementMother::scheduledFor($datetime, false, $title, new AnnouncementId($id)));
    }

    #[Given('announcement :title with id :id is scheduled for :datetime with notification')]
    public function announcementIsScheduledForWithNotification(string $title, string $id, string $datetime): void
    {
        $this->getAnnouncementRepository()->add(AnnouncementMother::scheduledFor($datetime, true, $title, new AnnouncementId($id)));
    }

    #[Given('announcement :id is translated to :locale as :title')]
    public function announcementIsTranslatedTo(string $id, string $locale, string $title): void
    {
        $this->getAnnouncement($id)->translate($title, $title . ' summary', '# ' . $title, null, null, null, Locale::from($locale));
    }

    #[Given('announcement :id has a manual in :locale')]
    public function announcementHasAManualIn(string $id, string $locale): void
    {
        $announcement = $this->getAnnouncement($id);
        $path = sprintf('%s/%s.pdf', $id, $locale);

        $this->retranslate($announcement, Locale::from($locale), manualFileName: 'manual.pdf', manualPath: $path);

        $this->getAnnouncementManualStorage()->add($path, '%PDF-1.4 test contents');
    }

    #[Given('announcement :id has the video :url in :locale')]
    public function announcementHasTheVideoIn(string $id, string $url, string $locale): void
    {
        $this->retranslate($this->getAnnouncement($id), Locale::from($locale), video: YouTubeVideo::fromUrl($url));
    }

    #[When('I schedule the publication for :datetime with notification')]
    public function iScheduleThePublicationForWithNotification(string $datetime): void
    {
        $form = $this->client->getCrawler()->selectButton('Publicar')->form();
        $formName = $form->getFormNode()->getAttribute('name');

        $form[sprintf('%s[publishAt]', $formName)] = $datetime;
        $form[sprintf('%s[notifyUsers]', $formName)] = '1';

        $this->client->submit($form);
    }

    #[When('the scheduled announcements are published')]
    public function theScheduledAnnouncementsArePublished(): void
    {
        $container = $this->client->getContainer();

        $admins = $container->get(UserRepository::class)->findByFilter(new UserFilter(role: Role::Admin, limit: 1));

        Assert::assertNotEmpty($admins, 'No admin user found');

        $container->get(DeferredCommandMessenger::class)->register();

        $service = $container->get(PublishScheduledAnnouncementsService::class);

        $service(new PublishScheduledAnnouncementsRequest(), $admins[0]);
    }

    #[Then('announcement :id is published')]
    public function announcementIsPublished(string $id): void
    {
        Assert::assertTrue($this->getAnnouncement($id)->isPublished());
    }

    #[Then('I see :value in the publication date field')]
    public function iSeeInThePublicationDateField(string $value): void
    {
        $form = $this->client->getCrawler()->selectButton('Publicar')->form();

        $field = $form->get(sprintf('%s[publishAt]', $form->getFormNode()->getAttribute('name')));

        Assert::assertInstanceOf(FormField::class, $field);
        Assert::assertSame($value, $field->getValue());
    }

    #[Then('announcement :id is scheduled for :datetime UTC')]
    public function announcementIsScheduledForUtc(string $id, string $datetime): void
    {
        $scheduledAt = $this->getAnnouncement($id)->scheduledAt();

        Assert::assertNotNull($scheduledAt);
        Assert::assertSame($datetime, $scheduledAt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i'));
    }

    #[Then('announcement :id is not published')]
    public function announcementIsNotPublished(string $id): void
    {
        Assert::assertFalse($this->getAnnouncement($id)->isPublished());
    }

    #[Then('a deferred :command command is queued in the low priority transport')]
    public function aDeferredCommandIsQueuedInTheLowPriorityTransport(string $command): void
    {
        $queued = [];

        foreach ($this->getLowPriorityTransport()->getSent() as $envelope) {
            $message = $envelope->getMessage();

            if ($message instanceof DeferredLowMessage) {
                $queued[] = (new \ReflectionClass($message->command()))->getShortName();
            }
        }

        Assert::assertContains($command, $queued);
    }

    #[Then('no deferred command is queued in the low priority transport')]
    public function noDeferredCommandIsQueuedInTheLowPriorityTransport(): void
    {
        Assert::assertCount(0, $this->getLowPriorityTransport()->getSent());
    }

    #[Then('the response content type is :type')]
    public function theResponseContentTypeIs(string $type): void
    {
        Assert::assertSame($type, $this->client->getResponse()->headers->get('Content-Type'));
    }

    private function retranslate(
        Announcement $announcement,
        Locale $locale,
        ?YouTubeVideo $video = null,
        ?string $manualFileName = null,
        ?string $manualPath = null,
    ): void {
        $title = $announcement->title($locale);
        $summary = $announcement->summary($locale);
        $body = $announcement->body($locale);
        $video ??= $announcement->youtubeVideo($locale);
        $manualFileName ??= $announcement->manualFileName($locale);
        $manualPath ??= $announcement->manualPath($locale);

        if (LocaleGetter::default() === $locale) {
            $announcement->update($announcement->category(), $announcement->date(), $announcement->version(), $title, $summary, $body, $video, $manualFileName, $manualPath);

            return;
        }

        $announcement->translate($title, $summary, $body, $video, $manualFileName, $manualPath, $locale);
    }

    #[Given('changelog entry :slug titled :title in category :category exists')]
    public function changelogEntryExists(string $slug, string $title, string $category): void
    {
        $this->getChangelogEntryRepository()->add(new ChangelogEntry(
            slug: new ChangelogEntrySlug($slug),
            title: $title,
            date: new \DateTimeImmutable('NOW'),
            category: ChangelogCategory::from($category),
            summary: $title . ' summary',
            body: '# ' . $title . "\n\nContenido de prueba.",
        ));
    }

    #[Given('changelog entry :slug is not published')]
    public function changelogEntryIsNotPublished(string $slug): void
    {
        $this->getChangelogEntryRepository()->add(new ChangelogEntry(
            slug: new ChangelogEntrySlug($slug),
            title: 'Entrada sin publicar',
            date: new \DateTimeImmutable('NOW'),
            category: ChangelogCategory::Fix,
            summary: 'Resumen',
            body: 'Cuerpo',
            published: false,
        ));
    }

    #[Given('changelog entry :slug has a user manual')]
    public function changelogEntryHasAUserManual(string $slug): void
    {
        $this->replaceEntry($slug, static fn(ChangelogEntry $entry): ChangelogEntry => new ChangelogEntry(
            slug: $entry->slug,
            title: $entry->title,
            date: $entry->date,
            category: $entry->category,
            summary: $entry->summary,
            body: $entry->body,
            version: $entry->version,
            planSlug: $entry->planSlug,
            manualFileName: $slug . '.pdf',
            technicalManualFileName: $entry->technicalManualFileName,
            published: $entry->published,
        ));

        $this->getManualStorage()->add($slug . '.pdf', '%PDF-1.4 test contents');
    }

    #[Given('changelog entry :slug has a technical manual')]
    public function changelogEntryHasATechnicalManual(string $slug): void
    {
        $this->replaceEntry($slug, static fn(ChangelogEntry $entry): ChangelogEntry => new ChangelogEntry(
            slug: $entry->slug,
            title: $entry->title,
            date: $entry->date,
            category: $entry->category,
            summary: $entry->summary,
            body: $entry->body,
            version: $entry->version,
            planSlug: $entry->planSlug,
            manualFileName: $entry->manualFileName,
            technicalManualFileName: $slug . '.tech.md',
            published: $entry->published,
        ));

        $this->getManualStorage()->add($slug . '.tech.md', '# Manual técnico de prueba');
    }

    private function replaceEntry(string $slug, \Closure $replace): void
    {
        $repository = $this->getChangelogEntryRepository();

        $existing = $repository->findBySlug(new ChangelogEntrySlug($slug));

        if (null === $existing) {
            $existing = new ChangelogEntry(
                slug: new ChangelogEntrySlug($slug),
                title: 'Entrada ' . $slug,
                date: new \DateTimeImmutable('NOW'),
                category: ChangelogCategory::Feature,
                summary: 'Resumen',
                body: 'Cuerpo',
            );
        }

        $repository->remove(new ChangelogEntrySlug($slug));
        $repository->add($replace($existing));
    }
}
