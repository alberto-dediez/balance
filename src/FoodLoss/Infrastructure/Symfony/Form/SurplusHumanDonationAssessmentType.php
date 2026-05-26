<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Infrastructure\Symfony\Form\Type\ReasonIdType;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\RecipientOrganizationIdType;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/** @extends AbstractType<mixed> */
class SurplusHumanDonationAssessmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('canBeHumanDonated', BooleanType::class, [
                'label' => new TranslatableMessage('¿Este excedente es apto y seguro para ser donado a entidades sociales?'),
                'required' => true,
                'placeholder' => new TranslatableMessage('Seleccionar'),
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('donationDate', DateType::class, [
                'label' => new TranslatableMessage('Fecha de la donación'),
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'row_attr' => [
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name$="[canBeHumanDonated]"]',
                    'data-showable-condition-value' => '1',
                ],
            ])
            ->add('batch', TextType::class, [
                'label' => new TranslatableMessage('Lote'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Assert\Length(max: 255)],
                'row_attr' => [
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name$="[canBeHumanDonated]"]',
                    'data-showable-condition-value' => '1',
                ],
            ])
            ->add('recipientOrganizationId', RecipientOrganizationIdType::class, [
                'label' => new TranslatableMessage('Entidad receptora'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Seleccionar entidad receptora'),
                'row_attr' => [
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name$="[canBeHumanDonated]"]',
                    'data-showable-condition-value' => '1',
                ],
            ])
            ->add('createDeliveryLog', BooleanType::class, [
                'label' => new TranslatableMessage('¿Desea registrar esta donación en el registro de entregas?'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Seleccionar'),
                'row_attr' => [
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name$="[canBeHumanDonated]"]',
                    'data-showable-condition-value' => '1',
                ],
            ])
            ->add('reasonId', ReasonIdType::class, [
                'label' => new TranslatableMessage('Motivo por el que no se puede donar'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Seleccionar motivo (opcional)'),
                'row_attr' => [
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name$="[canBeHumanDonated]"]',
                    'data-showable-condition-value' => '0',
                ],
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
            ->add('continue', SubmitType::class, [
                'label' => new TranslatableMessage('Continuar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'constraints' => [
                new Assert\Callback(function (mixed $data, ExecutionContextInterface $context): void {
                    if (!is_array($data) || true !== $data['canBeHumanDonated']) {
                        return;
                    }

                    if (null === $data['donationDate']) {
                        $context->buildViolation('human_donation.donation_date.not_null')
                            ->atPath('[donationDate]')
                            ->addViolation();
                    }

                    if (null === $data['recipientOrganizationId']) {
                        $context->buildViolation('human_donation.recipient_organization.not_null')
                            ->atPath('[recipientOrganizationId]')
                            ->addViolation();
                    }

                    if (null === $data['createDeliveryLog']) {
                        $context->buildViolation('human_donation.create_delivery_log.not_null')
                            ->atPath('[createDeliveryLog]')
                            ->addViolation();
                    }
                }),
            ],
        ]);
    }
}
