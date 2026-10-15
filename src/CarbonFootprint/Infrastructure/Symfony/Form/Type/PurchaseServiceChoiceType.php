<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceFilter;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<mixed>
 */
class PurchaseServiceChoiceType extends AbstractType
{
    public function __construct(
        private readonly PurchaseServiceRepository $purchaseServiceRepository,
        private readonly TranslatorInterface $translator,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach ($this->purchaseServiceRepository->findByFilter(new PurchaseServiceFilter(limit: 0)) as $purchaseService) {
            $choices[$this->label($purchaseService, $choices)] = (string) $purchaseService->id();
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    /** @param array<string, string> $choices */
    private function label(PurchaseService $purchaseService, array $choices): string
    {
        $label = sprintf(
            '%s - %s',
            $purchaseService->name(),
            $this->translator->trans($purchaseService->category()->value, [], 'purchaseServiceCategoryType'),
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
