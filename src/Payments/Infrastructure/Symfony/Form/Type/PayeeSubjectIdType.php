<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Symfony\Form\Type;

use App\Payments\Domain\Model\Payee\PayeeSubjectDirectory;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class PayeeSubjectIdType extends AbstractType
{
    public function __construct(private readonly PayeeSubjectDirectory $subjectDirectory) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $groups = [
            PayeeType::Account->value => 'Cuentas',
            PayeeType::Project->value => 'Proyectos',
        ];

        $choices = [];

        foreach (PayeeType::cases() as $type) {
            foreach ($this->subjectDirectory->subjects($type) as $subjectId => $label) {
                $choices[$groups[$type->value]][$label] = $subjectId;
            }
        }

        $resolver->setDefaults(['choices' => $choices]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
