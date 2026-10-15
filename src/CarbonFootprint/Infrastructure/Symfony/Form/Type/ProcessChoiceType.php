<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\Process\Process;
use App\CarbonFootprint\Domain\Model\Process\ProcessFilter;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class ProcessChoiceType extends AbstractType
{
    public function __construct(
        private readonly ProcessRepository $processRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach ($this->processRepository->findByFilter(new ProcessFilter(limit: 0)) as $process) {
            $choices[$this->label($process, $choices)] = (string) $process->id();
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    /** @param array<string, string> $choices */
    private function label(Process $process, array $choices): string
    {
        $label = (string) $process;
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
