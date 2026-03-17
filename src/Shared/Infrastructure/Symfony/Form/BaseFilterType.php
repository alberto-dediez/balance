<?php

namespace App\Shared\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/** @extends AbstractType<mixed> */
class BaseFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('limit', HiddenType::class)
            ->add('page', HiddenType::class)
            ->add('orderBy', HiddenType::class)
            ->add('orderWay', HiddenType::class)
            ->add('reset', SubmitType::class, [
                'label' => new TranslatableMessage('Resetear filtro')
            ])
            ->add('filter', SubmitType::class, [
                'label' => new TranslatableMessage('Filtrar')
            ])
        ;
    }
}
