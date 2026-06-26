<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\CateringItem\EmissionFactor\CreateYear\CreateEmissionFactorsYearRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<CreateEmissionFactorsYearRequest> */
class CreateCateringItemEmissionFactorsYearType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $builder
            ->add('targetYear', IntegerType::class, [
                'label' => new TranslatableMessage('Año destino'),
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\Range(min: 1900, max: $currentYear),
                ],
            ])
            ->add('create', SubmitType::class, [
                'label' => new TranslatableMessage('Copiar factores'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateEmissionFactorsYearRequest::class,
        ]);
    }
}
