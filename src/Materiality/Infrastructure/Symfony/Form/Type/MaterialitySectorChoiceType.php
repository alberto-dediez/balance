<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Symfony\Form\Type;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<mixed> */
class MaterialitySectorChoiceType extends AbstractType
{
    public function __construct(private readonly MaterialitySectorRepository $materialitySectorRepository) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['multiple']) {
            $builder->addModelTransformer(new CallbackTransformer(
                static fn(?array $materialitySectorIds): array => array_map(
                    static fn(MaterialitySectorId $materialitySectorId): string => (string) $materialitySectorId,
                    $materialitySectorIds ?? [],
                ),
                static fn(?array $materialitySectorIds): array => array_values(array_map(
                    static fn(string $materialitySectorId): MaterialitySectorId => new MaterialitySectorId($materialitySectorId),
                    $materialitySectorIds ?? [],
                )),
            ));

            return;
        }

        $builder->addModelTransformer(new CallbackTransformer(
            static fn(?MaterialitySectorId $materialitySectorId): ?string => null !== $materialitySectorId ? (string) $materialitySectorId : null,
            static fn(?string $materialitySectorId): ?MaterialitySectorId => null !== $materialitySectorId && '' !== $materialitySectorId ? new MaterialitySectorId($materialitySectorId) : null,
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => fn(Options $options): array => array_keys($this->names()),
            'choice_value' => static fn(?string $materialitySectorId): ?string => $materialitySectorId,
            'choice_label' => function (Options $options): \Closure {
                $names = $this->names();

                return static fn(string $materialitySectorId): string => $names[$materialitySectorId];
            },
            'choice_translation_domain' => false,
            'placeholder' => static fn(Options $options): ?TranslatableMessage => $options['multiple'] ? null : new TranslatableMessage('Todos los sectores'),
            'required' => false,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function names(): array
    {
        $names = [];

        foreach ($this->materialitySectorRepository->findAll() as $materialitySector) {
            $names[(string) $materialitySector->id()] = $materialitySector->name();
        }

        return $names;
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
