<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form;

use App\Balance\Application\CreditPurchase\Assign\AssignCreditPurchaseRequest;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Infrastructure\Symfony\Form\Type\CreditPurchasePaymentTypeType;
use App\Library\Domain\Model\Media\MediaType;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Library\Infrastructure\Symfony\Form\Type\MediaPickerType;
use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Infrastructure\Symfony\Form\Type\AccountIdType;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class AssignCreditPurchaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('accountId', AccountIdType::class, [
                'label' => new TranslatableMessage('Cuenta'),
                'required' => true,
                'placeholder' => new TranslatableMessage('Selecciona la cuenta'),
                'feature' => AccountFeature::Compensation,
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ])
            ->add('projectId', AutocompleteType::class, [
                'label' => new TranslatableMessage('Proyecto'),
                'required' => true,
                'placeholder' => new TranslatableMessage('Selecciona el proyecto'),
                'choices' => $options['projects'],
                'choice_value' => static fn(?ProjectId $projectId): ?string => $projectId?->id(),
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ])
            ->add('purchasedTonnes', NumberType::class, [
                'label' => new TranslatableMessage('Toneladas asignadas'),
                'required' => true,
                'scale' => 2,
                'help' => new TranslatableMessage('Si la cuenta tiene una solicitud pendiente para este proyecto, se validará con estos datos en lugar de crear un crédito nuevo'),
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                ],
            ])
            ->add('paymentType', CreditPurchasePaymentTypeType::class, [
                'label' => new TranslatableMessage('Tipo de pago'),
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ])
            ->add('reference', TextType::class, [
                'label' => new TranslatableMessage('Identificador de la compra'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('amount', NumberType::class, [
                'label' => new TranslatableMessage('Coste del lote'),
                'required' => false,
                'scale' => 2,
                'constraints' => [
                    new Assert\PositiveOrZero(),
                ],
            ])
            ->add('certificate', MediaPickerType::class, [
                'label' => new TranslatableMessage('Certificado'),
                'required' => false,
                'types' => [MediaType::Document, MediaType::Image],
                'visibility' => MediaVisibility::System,
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Asignar créditos'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AssignCreditPurchaseRequest::class,
            'projects' => [],
        ]);

        $resolver->setAllowedTypes('projects', 'array');
    }
}
