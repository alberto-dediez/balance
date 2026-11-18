<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Infrastructure\Persistence;

use App\Changelog\Domain\Model\GuideArticle\GuideArticle;
use App\Changelog\Domain\Model\GuideArticle\GuideArticleFilter;
use App\Changelog\Domain\Model\GuideArticle\GuideArticlePath;
use App\Changelog\Domain\Model\GuideArticle\GuideArticleRepository;
use App\Changelog\Domain\Model\GuideArticle\GuideProfile;
use App\Changelog\Infrastructure\Markdown\GuideArticleParser;
use App\Changelog\Infrastructure\Persistence\Filesystem\FilesystemGuideArticleRepository;

class FilesystemGuideArticleRepositoryTest extends GuideArticleRepositoryTest
{
    private string $guidePath;

    protected function setUp(): void
    {
        $this->guidePath = sys_get_temp_dir() . '/guide-fs-repo-test-' . uniqid();
        mkdir($this->guidePath, 0o775, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->guidePath);
    }

    protected function givenArticles(array $articles): GuideArticleRepository
    {
        foreach ($articles as $article) {
            $this->writeArticle($article);
        }

        return $this->createRepository($this->guidePath);
    }

    public function testIndexFilesAreNotArticles(): void
    {
        $repository = $this->createRepository(__DIR__ . '/../fixtures/guide');

        $paths = array_map(
            static fn(GuideArticle $article): string => (string) $article->path,
            $repository->findByFilter(new GuideArticleFilter(GuideProfile::Admin)),
        );

        $this->assertNotContains('index', $paths);
        $this->assertContains('seguridad/organizacion/usuarios', $paths);
    }

    public function testInvalidFilesAreOmitted(): void
    {
        $repository = $this->createRepository(__DIR__ . '/../fixtures/guide');

        $paths = array_map(
            static fn(GuideArticle $article): string => (string) $article->path,
            $repository->findByFilter(new GuideArticleFilter(GuideProfile::Admin)),
        );

        $this->assertNotContains('general/sin-titulo', $paths);
        $this->assertNotContains('general/sin-frontmatter', $paths);
        $this->assertContains('general/fecha-corta', $paths);
    }

    public function testAMissingProfileFolderYieldsAnEmptyList(): void
    {
        $repository = $this->createRepository($this->guidePath . '-missing');

        $this->assertSame([], $repository->findByFilter(new GuideArticleFilter(GuideProfile::Owner)));
        $this->assertSame(0, $repository->totalResultsByFilter(new GuideArticleFilter(GuideProfile::Owner)));
        $this->assertNull($repository->find(GuideProfile::Owner, new GuideArticlePath('a/x')));
    }

    public function testFindFromAnotherProfileReadsItsOwnFolder(): void
    {
        $repository = $this->createRepository(__DIR__ . '/../fixtures/guide');

        $admin = $repository->find(GuideProfile::Admin, new GuideArticlePath('seguridad/organizacion/usuarios'));
        $owner = $repository->find(GuideProfile::Owner, new GuideArticlePath('seguridad/organizacion/usuarios'));

        $this->assertSame('Usuarios', $admin?->title);
        $this->assertSame('Usuarios de mi cuenta', $owner?->title);
        $this->assertNull($repository->find(GuideProfile::User, new GuideArticlePath('seguridad/organizacion/usuarios')));
    }

    public function testASymlinkLeavingTheProfileFolderIsNotFollowed(): void
    {
        mkdir($this->guidePath . '/admin', 0o775, true);
        mkdir($this->guidePath . '/owner', 0o775, true);
        file_put_contents($this->guidePath . '/owner/secreto.md', "---\ntitle: Secreto\nsummary: Resumen\n---\n\nCuerpo\n");
        symlink($this->guidePath . '/owner/secreto.md', $this->guidePath . '/admin/secreto.md');

        $repository = $this->createRepository($this->guidePath);

        $this->assertNull($repository->find(GuideProfile::Admin, new GuideArticlePath('secreto')));
    }

    private function createRepository(string $guidePath): GuideArticleRepository
    {
        return new FilesystemGuideArticleRepository($guidePath, new GuideArticleParser());
    }

    private function writeArticle(GuideArticle $article): void
    {
        $file = sprintf('%s/%s/%s.md', $this->guidePath, $article->profile->value, (string) $article->path);

        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0o775, true);
        }

        file_put_contents($file, sprintf(
            "---\ntitle: %s\nsummary: %s\nmenu: \"%s\"\n---\n\n%s\n",
            $article->title,
            $article->summary,
            $article->menu,
            $article->body,
        ));
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (new \DirectoryIterator($directory) as $item) {
            if ($item->isDot()) {
                continue;
            }

            if ($item->isLink() || $item->isFile()) {
                unlink($item->getPathname());

                continue;
            }

            $this->removeDirectory($item->getPathname());
        }

        rmdir($directory);
    }
}
