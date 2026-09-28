<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Symfony\Form\Type;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicFilter;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<mixed> */
class MaterialityTopicChoiceType extends AbstractType
{
    public function __construct(private readonly MaterialityTopicRepository $materialityTopicRepository) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            static fn(?MaterialityTopicId $materialityTopicId): ?string => null !== $materialityTopicId ? (string) $materialityTopicId : null,
            static fn(?string $materialityTopicId): ?MaterialityTopicId => null !== $materialityTopicId && '' !== $materialityTopicId ? new MaterialityTopicId($materialityTopicId) : null,
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'include_inactive' => false,
            'choices' => fn(Options $options): array => array_keys($this->names($options['include_inactive'])),
            'choice_value' => static fn(?string $materialityTopicId): ?string => $materialityTopicId,
            'choice_label' => function (Options $options): \Closure {
                $names = $this->names($options['include_inactive']);

                return static fn(string $materialityTopicId): string => $names[$materialityTopicId];
            },
            'choice_translation_domain' => false,
            'placeholder' => new TranslatableMessage('Todas las categorías'),
            'required' => false,
        ]);

        $resolver->setAllowedTypes('include_inactive', 'bool');
    }

    /**
     * @return array<string, string>
     */
    private function names(bool $includeInactive): array
    {
        $materialityTopics = $includeInactive
            ? $this->materialityTopicRepository->findByFilter(new MaterialityTopicFilter(limit: 0))
            : $this->materialityTopicRepository->findActive();

        $names = [];

        foreach ($materialityTopics as $materialityTopic) {
            $names[(string) $materialityTopic->id()] = $materialityTopic->name();
        }

        return $names;
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
