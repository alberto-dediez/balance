<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\DonationAgreement\Register\RegisterDonationAgreementRequest;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\RecipientOrganizationIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<RegisterDonationAgreementRequest> */
class RegisterDonationAgreementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('recipientOrganizationId', RecipientOrganizationIdType::class, [
                'label' => new TranslatableMessage('Entidad receptora'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('startDate', DateType::class, [
                'label' => new TranslatableMessage('Fecha de inicio'),
                'required' => true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('expirationDate', DateType::class, [
                'label' => new TranslatableMessage('Fecha de expiración'),
                'required' => true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('pdfFile', FileType::class, [
                'label' => new TranslatableMessage('Convenio (PDF)'),
                'required' => true,
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => new TranslatableMessage('Por favor, sube un fichero PDF válido.'),
                    ]),
                ],
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterDonationAgreementRequest::class,
        ]);
    }
}
