<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Navigation;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\Search\SearchCategory;
use App\Shared\Application\Search\SearchProvider;
use App\Shared\Application\Search\SearchResult;
use App\Shared\Application\Search\SearchTerm;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class NavigationSearchProvider implements SearchProvider
{
    public const int POSITION = 0;

    public function __construct(
        private readonly NodeLister $nodeLister,
        private readonly TranslatorInterface $translator,
    ) {}

    public function category(): SearchCategory
    {
        return new SearchCategory('navigation', 'Acciones', 'solar:hamburger-menu-line-duotone', self::POSITION);
    }

    public function isAvailableTo(User $user): bool
    {
        return [] !== $this->entries();
    }

    public function search(SearchTerm $term, User $user, int $limit): array
    {
        $words = preg_split('/\s+/', $this->normalize((string) $term), -1, PREG_SPLIT_NO_EMPTY);

        if (false === $words) {
            return [];
        }

        $results = [];

        foreach ($this->entries() as $entry) {
            foreach ($words as $word) {
                if (!str_contains($entry->haystack, $word)) {
                    continue 2;
                }
            }

            $results[] = new SearchResult(
                title: $entry->title,
                subtitle: $entry->subtitle,
                url: $entry->url,
                icon: $entry->icon,
            );
        }

        return $results;
    }

    /**
     * @return list<NavigationEntry>
     */
    private function entries(): array
    {
        $entries = [];

        foreach ($this->nodeLister->nodes() as $node) {
            if (null !== $node->target && [] === $node->items) {
                $entries[] = $this->entry($this->label($node->label), null, $node->target, $node->icon);
            }

            foreach ($node->items as $item) {
                if (null === $item->target) {
                    continue;
                }

                $entries[] = $this->entry($this->label($item->label), $this->label($node->label), $item->target, $item->icon);
            }
        }

        return $entries;
    }

    private function entry(string $title, ?string $subtitle, string $url, string $icon): NavigationEntry
    {
        return new NavigationEntry(
            title: $title,
            subtitle: $subtitle,
            url: $url,
            icon: $icon,
            haystack: $this->normalize(trim($subtitle . ' ' . $title)),
        );
    }

    private function label(string|TranslatableMessage $label): string
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
