<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\Transport\Transport;
use App\CarbonFootprint\Domain\Model\Transport\TransportFilter;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<mixed>
 */
class TransportChoiceType extends AbstractType
{
    public function __construct(
        private readonly TransportRepository $transportRepository,
        private readonly TranslatorInterface $translator,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach ($this->transportRepository->findByFilter(new TransportFilter(limit: 0)) as $transport) {
            $choices[$this->label($transport, $choices)] = (string) $transport->id();
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    /** @param array<string, string> $choices */
    private function label(Transport $transport, array $choices): string
    {
        $label = sprintf(
            '%s - %s',
            $transport->name(),
            $this->translator->trans($transport->vehicle()->value, [], 'transportVehicleType'),
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
