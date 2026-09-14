<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Changelog;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;
use App\Changelog\Infrastructure\Persistence\InMemory\InMemoryChangelogEntryRepository;
use App\Changelog\Infrastructure\Storage\InMemoryManualStorage;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

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
