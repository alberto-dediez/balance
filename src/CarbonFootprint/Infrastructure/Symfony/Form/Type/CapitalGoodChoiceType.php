<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGood;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodFilter;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<mixed>
 */
class CapitalGoodChoiceType extends AbstractType
{
    public function __construct(
        private readonly CapitalGoodRepository $capitalGoodRepository,
        private readonly TranslatorInterface $translator,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach ($this->capitalGoodRepository->findByFilter(new CapitalGoodFilter(limit: 0)) as $capitalGood) {
            $choices[$this->label($capitalGood, $choices)] = (string) $capitalGood->id();
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    /** @param array<string, string> $choices */
    private function label(CapitalGood $capitalGood, array $choices): string
    {
        $label = sprintf(
            '%s - %s',
            $capitalGood->name(),
            $this->translator->trans($capitalGood->category()->value, [], 'capitalGoodCategoryType'),
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
