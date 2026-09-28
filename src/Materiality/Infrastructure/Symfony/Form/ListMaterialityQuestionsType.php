<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Symfony\Form;

use App\Materiality\Application\MaterialityQuestion\List\ListMaterialityQuestionsRequest;
use App\Materiality\Infrastructure\Symfony\Form\Type\MaterialityCriterionType;
use App\Materiality\Infrastructure\Symfony\Form\Type\MaterialityTopicChoiceType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListMaterialityQuestionsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('text', TextType::class, [
                'label' => new TranslatableMessage('Pregunta'),
                'required' => false,
            ])
            ->add('criterion', MaterialityCriterionType::class, [
                'label' => new TranslatableMessage('Criterio'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
            ->add('topicId', MaterialityTopicChoiceType::class, [
                'label' => new TranslatableMessage('Categoría'),
                'include_inactive' => true,
            ])
            ->add('active', BooleanType::class, [
                'label' => new TranslatableMessage('Activa'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas'),
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListMaterialityQuestionsRequest::class,
        ]);
    }
}
