<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemFilter;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class CateringItemChoiceType extends AbstractType
{
    public function __construct(
        private readonly CateringItemRepository $cateringItemRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach ($this->cateringItemRepository->findByFilter(new CateringItemFilter(limit: 0)) as $cateringItem) {
            $choices[$this->label($cateringItem, $choices)] = (string) $cateringItem->id();
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    /** @param array<string, string> $choices */
    private function label(CateringItem $cateringItem, array $choices): string
    {
        $label = (string) $cateringItem;
        $candidate = $label;
        $i = 0;

        while (isset($choices[$candidate])) {
            $candidate = sprintf('%s (%d)', $label, ++$i);
        }

        return $candidate;
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
