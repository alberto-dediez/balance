<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemFilter;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<mixed>
 */
class MerchandisingItemChoiceType extends AbstractType
{
    public function __construct(
        private readonly MerchandisingItemRepository $merchandisingItemRepository,
        private readonly TranslatorInterface $translator,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach ($this->merchandisingItemRepository->findByFilter(new MerchandisingItemFilter(limit: 0)) as $merchandisingItem) {
            $choices[$this->label($merchandisingItem, $choices)] = (string) $merchandisingItem->id();
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    /** @param array<string, string> $choices */
    private function label(MerchandisingItem $merchandisingItem, array $choices): string
    {
        $label = sprintf(
            '%s - %s',
            $merchandisingItem->name(),
            $this->translator->trans($merchandisingItem->category()->value, [], 'merchandisingItemCategoryType'),
        );

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
