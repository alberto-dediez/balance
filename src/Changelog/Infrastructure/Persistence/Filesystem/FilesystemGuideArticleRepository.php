<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\Persistence\Filesystem;

use App\Changelog\Domain\Model\GuideArticle\GuideArticle;
use App\Changelog\Domain\Model\GuideArticle\GuideArticleFilter;
use App\Changelog\Domain\Model\GuideArticle\GuideArticlePath;
use App\Changelog\Domain\Model\GuideArticle\GuideArticleRepository;
use App\Changelog\Domain\Model\GuideArticle\GuideProfile;
use App\Changelog\Domain\Model\GuideArticle\InvalidGuideArticleException;
use App\Changelog\Infrastructure\Markdown\GuideArticleParser;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
class FilesystemGuideArticleRepository implements GuideArticleRepository
{
    public function __construct(
        private readonly string $guidePath,
        private readonly GuideArticleParser $parser,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function find(GuideProfile $profile, GuideArticlePath $path): ?GuideArticle
    {
        $file = sprintf('%s/%s/%s.md', $this->guidePath, $profile->value, (string) $path);

        if (!is_file($file)) {
            return null;
        }

        $real = realpath($file);
        $root = realpath($this->guidePath . '/' . $profile->value);

        if (false === $real || false === $root || !str_starts_with($real, $root . '/')) {
            return null;
        }

        try {
            return $this->parser->parse($file, $profile, $path);
        } catch (InvalidGuideArticleException $exception) {
            $this->logger?->warning($exception->getMessage());

            return null;
        }
    }

    public function findByFilter(GuideArticleFilter $filter): array
    {
        $articles = array_values(array_filter($this->all($filter->profile), static fn(GuideArticle $article): bool => $article->matches($filter)));

        return array_slice($articles, $filter->page * $filter->limit, $filter->limit);
    }

    public function totalResultsByFilter(GuideArticleFilter $filter): int
    {
        return count(array_filter($this->all($filter->profile), static fn(GuideArticle $article): bool => $article->matches($filter)));
    }

    /**
     * @return list<GuideArticle>
     */
    private function all(GuideProfile $profile): array
    {
        $root = $this->guidePath . '/' . $profile->value;

        if (!is_dir($root)) {
            return [];
        }

        $articles = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile() || 'md' !== $file->getExtension()) {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($root) + 1, -3);

            if ('index' === $relative) {
                continue;
            }

            try {
                $path = new GuideArticlePath($relative);
            } catch (InvalidGuideArticleException $exception) {
                $this->logger?->warning($exception->getMessage());

                continue;
            }

            $article = $this->find($profile, $path);

            if ($article instanceof GuideArticle) {
                $articles[] = $article;
            }
        }

        usort($articles, static fn(GuideArticle $a, GuideArticle $b): int => [$a->menu, (string) $a->path] <=> [$b->menu, (string) $b->path]);

        return $articles;
    }
}
