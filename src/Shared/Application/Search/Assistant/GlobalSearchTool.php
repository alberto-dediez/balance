<?php

declare(strict_types=1);

namespace App\Shared\Application\Search\Assistant;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\AssistantTool;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;
use App\Shared\Application\Search\GlobalSearchRequest;
use App\Shared\Application\Search\GlobalSearchService;
use App\Shared\Application\Search\SearchCategoryResults;
use App\Shared\Application\Search\SearchResult;

class GlobalSearchTool implements AssistantTool
{
    private const array EXCLUDED_CATEGORIES = ['navigation', 'secrets', 'counterparties', 'candidates'];
    private const int TERM_MIN_LENGTH = 2;
    private const int TERM_MAX_LENGTH = 100;
    private const int DEFAULT_LIMIT = 5;
    private const int MAX_LIMIT = 10;

    public function __construct(
        private readonly GlobalSearchService $globalSearch,
    ) {}

    public function name(): string
    {
        return 'search';
    }

    public function label(): string
    {
        return 'Buscar en la plataforma';
    }

    public function description(): string
    {
        return 'Busca un término por nombre en toda la plataforma (clientes, proyectos, tareas, propuestas, etc.). Úsala para localizar algo por nombre antes de pedir su detalle con la herramienta view_* correspondiente.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::string('term', 'Término', 'Texto a buscar (mínimo 2 caracteres).', self::TERM_MAX_LENGTH)->required(),
            ToolParameter::integer('limit', 'Límite', 'Número máximo de resultados por categoría (por defecto 5, máximo 10).', 1, self::MAX_LIMIT),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->globalSearch->isAvailableTo($user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $term = trim((string) $arguments->string('term'));

        if (mb_strlen($term) < self::TERM_MIN_LENGTH) {
            return new ToolResult(summary: '0 resultados', data: ['categories' => []]);
        }

        $limit = min(self::MAX_LIMIT, max(1, $arguments->int('limit') ?? self::DEFAULT_LIMIT));
        $response = ($this->globalSearch)(new GlobalSearchRequest($term, $limit), $user);

        $categories = array_values(array_filter(
            $response->categories,
            static fn(SearchCategoryResults $category): bool => !in_array($category->category->key, self::EXCLUDED_CATEGORIES, true),
        ));

        return new ToolResult(
            summary: sprintf('%d categorías con resultados', count($categories)),
            data: [
                'categories' => array_map(
                    static fn(SearchCategoryResults $category): array => [
                        'key' => $category->category->key,
                        'label' => $category->category->label,
                        'results' => array_map(
                            static fn(SearchResult $result): array => [
                                'title' => $result->title,
                                'subtitle' => $result->subtitle,
                                'href' => null === $result->route ? null : ToolLink::href($result->route, $result->routeParameters),
                            ],
                            $category->results,
                        ),
                    ],
                    $categories,
                ),
            ],
        );
    }
}
