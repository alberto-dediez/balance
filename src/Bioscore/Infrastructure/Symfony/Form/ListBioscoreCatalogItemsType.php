<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\Symfony\Form;

use App\Bioscore\Application\BioscoreCatalogItem\List\ListBioscoreCatalogItemsRequest;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreMappingStatus;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListBioscoreCatalogItemsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false,
            ])
            ->add('status', EnumType::class, [
                'label' => new TranslatableMessage('Estado'),
                'class' => BioscoreMappingStatus::class,
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
                'choice_label' => static fn(BioscoreMappingStatus $status): TranslatableMessage => new TranslatableMessage($status->value, [], 'bioscoreMappingStatus'),
            ])
            ->add('withdrawn', BooleanType::class, [
                'label' => new TranslatableMessage('Retirado'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListBioscoreCatalogItemsRequest::class,
        ]);
    }
}
