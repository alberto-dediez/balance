<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form;

use App\Balance\Application\Project\List\ListProjectsRequest;
use App\Balance\Infrastructure\Symfony\Form\Type\ProjectStatusType;
use App\Balance\Infrastructure\Symfony\Form\Type\ProjectTypeType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListProjectsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false,
            ])
            ->add('projectType', ProjectTypeType::class, [
                'label' => new TranslatableMessage('Tipo de proyecto'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
            ->add('projectStatus', ProjectStatusType::class, [
                'label' => new TranslatableMessage('Estado del proyecto'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
            ->add('mitecoId', TextType::class, [
                'label' => new TranslatableMessage('Código Registro Miteco'),
                'required' => false,
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ListProjectsRequest::class,
        ]);
    }
}
