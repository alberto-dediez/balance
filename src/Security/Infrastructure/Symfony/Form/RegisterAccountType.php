<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\EmissionFactorGroupChoiceType;
use App\Security\Application\Account\Register\RegisterAccountRequest;
use App\Shared\Infrastructure\Symfony\Form\DataTransformer\AggregateRootIdTransformer;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @extends AbstractType<RegisterAccountRequest>
 */
class RegisterAccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true,
            ])
            ->add('active', BooleanType::class, [
                'label' => new TranslatableMessage('Activa'),
                'required' => true,
            ])
            ->add('emissionFactorGroupId', EmissionFactorGroupChoiceType::class, [
                'label' => new TranslatableMessage('Grupo de coeficientes por defecto'),
                'required' => false,
            ])
            ->add('consultant', BooleanType::class, [
                'label' => new TranslatableMessage('Es consultora'),
                'required' => true,
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar'),
            ])
        ;

        $builder->get('emissionFactorGroupId')->addModelTransformer(
            new AggregateRootIdTransformer(EmissionFactorGroupId::class),
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterAccountRequest::class,
        ]);
    }
}
