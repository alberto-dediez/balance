<?php

declare(strict_types=1);

namespace App\Changelog\Application\GuideArticle\Assistant;

use App\Changelog\Application\GuideArticle\GuideArticleAuthorizer;
use App\Changelog\Application\GuideArticle\View\ViewGuideArticleRequest;
use App\Changelog\Application\GuideArticle\View\ViewGuideArticleService;
use App\Changelog\Domain\Model\GuideArticle\GuideArticle;
use App\Changelog\Domain\Model\GuideArticle\GuideArticleNotFoundException;
use App\Changelog\Domain\Model\GuideArticle\GuideArticlePath;
use App\Changelog\Domain\Model\GuideArticle\InvalidGuideArticleException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\SourceAssistantTool;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;

class ReadGuideArticleTool implements SourceAssistantTool
{
    public const string NOT_FOUND = 'El artículo no existe; búscalo con search_guide_articles.';

    public function __construct(
        private readonly ViewGuideArticleService $viewGuideArticle,
        private readonly GuideArticleAuthorizer $authorizer,
    ) {}

    public function name(): string
    {
        return 'read_guide_article';
    }

    public function label(): string
    {
        return 'Leer artículo de la guía';
    }

    public function description(): string
    {
        return 'Devuelve en Markdown un artículo de la guía de uso de la plataforma que corresponde al usuario. Usa el path devuelto por search_guide_articles.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::string('path', 'Artículo', 'Ruta del artículo tal como la devuelve search_guide_articles.')->required(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(GuideArticle::placeholder($this->authorizer->profileOf($user)), Action::View, $user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        try {
            $article = ($this->viewGuideArticle)(new ViewGuideArticleRequest($this->path($arguments)), $user)->article;
        } catch (GuideArticleNotFoundException) {
            throw InvalidToolArgumentsException::because(self::NOT_FOUND);
        }

        [$markdown, $truncated] = ToolResult::truncate($article->body);

        return new ToolResult(
            summary: $article->title,
            data: [
                'path' => (string) $article->path,
                'title' => $article->title,
                'menu' => $article->menu,
                'summary' => $article->summary,
                'updated' => $context->dateTime($article->updated),
                'markdown' => $markdown,
                'truncated' => $truncated,
            ],
            references: ['path' => (string) $article->path],
            link: new ToolLink('Ver guía', 'guide_article', ['path']),
        );
    }

    public function sourceHref(array $arguments): ?string
    {
        $path = $arguments['path'][0] ?? null;

        return null === $path ? null : ToolLink::href('guide_article', ['path' => $path]);
    }

    private function path(ToolArguments $arguments): GuideArticlePath
    {
        try {
            return new GuideArticlePath((string) $arguments->string('path'));
        } catch (InvalidGuideArticleException) {
            throw InvalidToolArgumentsException::because('«path»: no es una ruta de artículo válida.');
        }
    }
}
