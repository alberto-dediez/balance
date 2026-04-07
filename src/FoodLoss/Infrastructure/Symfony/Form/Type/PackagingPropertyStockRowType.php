<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\FoodLoss\Domain\Model\Packaging\PackagingPropertyStockData;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Infrastructure\Symfony\Form\DataTransformer\AggregateRootIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class PackagingPropertyStockRowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('propertyId', HiddenType::class)
            ->add('stock', IntegerType::class, [
                'label' => false,
                'required' => true,
                'constraints' => [
                    new Assert\GreaterThanOrEqual(0),
                ],
                'attr' => [
                    'min' => 0,
                    'class' => 'form-control form-control-sm',
                ],
            ])
        ;

        $builder->get('propertyId')->addModelTransformer(
            new AggregateRootIdTransformer(PropertyId::class),
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PackagingPropertyStockData::class,
        ]);
    }
}
