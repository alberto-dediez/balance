<?php

declare(strict_types=1);

namespace App\Changelog\Application\GuideArticle\Assistant;

use App\Changelog\Application\GuideArticle\GuideArticleAuthorizer;
use App\Changelog\Application\GuideArticle\List\GuideArticleMatch;
use App\Changelog\Application\GuideArticle\List\ListGuideArticlesRequest;
use App\Changelog\Application\GuideArticle\List\ListGuideArticlesService;
use App\Changelog\Domain\Model\GuideArticle\GuideArticle;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\AssistantTool;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;

class SearchGuideArticlesTool implements AssistantTool
{
    public function __construct(
        private readonly ListGuideArticlesService $listGuideArticles,
        private readonly GuideArticleAuthorizer $authorizer,
    ) {}

    public function name(): string
    {
        return 'search_guide_articles';
    }

    public function label(): string
    {
        return 'Buscar en la guía';
    }

    public function description(): string
    {
        return 'Busca en la guía de uso de la plataforma que corresponde al usuario. Sin texto devuelve el índice de artículos; con texto, los artículos que lo contienen con un fragmento. Lee después el artículo con read_guide_article usando su path.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::string('text', 'Texto', 'Palabras a buscar. Vacío: índice completo.'),
            ToolParameter::limit(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(GuideArticle::placeholder($this->authorizer->profileOf($user)), Action::List, $user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $response = ($this->listGuideArticles)(new ListGuideArticlesRequest($arguments->string('text'), $arguments->limit()), $user);

        return new ToolResult(
            summary: sprintf('%d artículos de la guía', $response->total),
            data: [
                'articles' => array_map(
                    static fn(GuideArticleMatch $match): array => [
                        'path' => (string) $match->article->path,
                        'title' => $match->article->title,
                        'menu' => $match->article->menu,
                        'summary' => $match->article->summary,
                        'excerpt' => $match->excerpt,
                        'href' => ToolLink::href('guide_article', ['path' => (string) $match->article->path]),
                    ],
                    $response->matches,
                ),
                'total' => $response->total,
            ],
        );
    }
}
