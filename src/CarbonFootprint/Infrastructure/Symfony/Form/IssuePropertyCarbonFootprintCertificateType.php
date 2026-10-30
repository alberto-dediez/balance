<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Issue\IssuePropertyCarbonFootprintCertificateRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<IssuePropertyCarbonFootprintCertificateRequest> */
class IssuePropertyCarbonFootprintCertificateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('stays', IntegerType::class, [
                'label' => new TranslatableMessage('Estancias del año'),
                'help' => new TranslatableMessage('Pernoctaciones de la propiedad en el año de la huella. Se usan para calcular los kg de CO₂eq por estancia.'),
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\Positive(),
                    new Assert\LessThanOrEqual(2147483647),
                ],
            ])
            ->add('issue', SubmitType::class, [
                'label' => new TranslatableMessage('Emitir certificado'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IssuePropertyCarbonFootprintCertificateRequest::class,
        ]);
    }
}
