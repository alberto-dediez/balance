<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Navigation;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\AssistantTool;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class ListScreensTool implements AssistantTool
{
    public function __construct(
        private readonly NodeLister $nodeLister,
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator,
    ) {}

    public function name(): string
    {
        return 'list_screens';
    }

    public function label(): string
    {
        return 'Listar pantallas';
    }

    public function description(): string
    {
        return 'Lista las pantallas del menú de la plataforma a las que el usuario puede ir, con su ubicación en el menú. Úsala cuando pregunten dónde está o cómo se llega a algo.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::string('term', 'Término', 'Texto que debe aparecer en el nombre o en la ubicación de la pantalla. Vacío: todas.'),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return [] !== $this->nodeLister->nodesFor($user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $words = preg_split('/\s+/', $this->normalize((string) $arguments->string('term')), -1, PREG_SPLIT_NO_EMPTY);
        $screens = [];

        foreach ($this->nodeLister->nodesFor($user) as $node) {
            $this->collect($node, [], false === $words ? [] : $words, $screens);
        }

        return new ToolResult(
            summary: sprintf('%d pantallas', count($screens)),
            data: ['screens' => $screens],
        );
    }

    /**
     * @param list<string>                                                $path
     * @param list<string>                                                $words
     * @param list<array{title: string, location: ?string, href: string}> $screens
     */
    private function collect(Node $node, array $path, array $words, array &$screens): void
    {
        if ([] !== $node->items) {
            $label = $this->translate($node->label);

            foreach ($node->items as $item) {
                $this->collect($item, [...$path, $label], $words, $screens);
            }

            return;
        }

        if (null === $node->target) {
            return;
        }

        $title = $this->translate($node->label);
        $location = [] === $path ? null : implode(' > ', $path);
        $haystack = $this->normalize(trim($location . ' ' . $title));

        foreach ($words as $word) {
            if (!str_contains($haystack, $word)) {
                return;
            }
        }

        $href = $this->href($node->target);

        if (null === $href) {
            return;
        }

        $screens[] = ['title' => $title, 'location' => $location, 'href' => $href];
    }

    private function href(string $target): ?string
    {
        $context = $this->router->getContext();
        $method = $context->getMethod();
        $context->setMethod('GET');

        try {
            $match = $this->router->match($target);
        } catch (ExceptionInterface) {
            return null;
        } finally {
            $context->setMethod($method);
        }

        $route = $match['_route'] ?? null;

        if (!is_string($route)) {
            return null;
        }

        $parameters = [];

        foreach ($match as $name => $value) {
            if (str_starts_with((string) $name, '_') || !is_scalar($value)) {
                continue;
            }

            $parameters[(string) $name] = (string) $value;
        }

        return ToolLink::href($route, $parameters);
    }

    private function translate(string|TranslatableMessage $label): string
    {
        return $label instanceof TranslatableMessage ? $label->trans($this->translator) : $this->translator->trans($label);
    }

    private function normalize(string $value): string
    {
        $decomposed = \Normalizer::normalize($value, \Normalizer::FORM_D);
        $withoutMarks = preg_replace('/\p{Mn}/u', '', false === $decomposed ? $value : $decomposed) ?? $value;

        return trim(preg_replace('/\s+/', ' ', mb_strtolower($withoutMarks)) ?? '');
    }
}
