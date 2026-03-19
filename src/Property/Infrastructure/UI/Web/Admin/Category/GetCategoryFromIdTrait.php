<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Category;

use App\Property\Domain\Model\Category\Category;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Category\CategoryRepository;
use Symfony\Component\Translation\TranslatableMessage;

trait GetCategoryFromIdTrait
{
    private function getCategoryFromId(string $categoryId, CategoryRepository $categoryRepository): Category
    {
        $category = $categoryRepository->find(new CategoryId($categoryId));

        if (null == $category) {
            throw $this->createNotFoundException(new TranslatableMessage('La categoría no existe'));
        }

        return $category;
    }
}
