<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Twig;

use App\Property\Domain\Model\Category\Category;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Category\CategoryRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CategoryExtension extends AbstractExtension
{
    /** @var Category[] */
    private array $categories = [];

    public function __construct(private readonly CategoryRepository $categoryRepository) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('category_name', $this->categoryName(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [

        ];
    }

    public function categoryName(?CategoryId $categoryId): ?Category
    {
        if (!$categoryId instanceof CategoryId) {
            return null;
        }

        if (!isset($this->categories[(string) $categoryId])) {
            $category = $this->categoryRepository->find($categoryId);

            if (null !== $category) {
                $this->categories[(string) $categoryId] = $category;
            }
        }

        return $this->categories[(string) $categoryId];
    }
}
