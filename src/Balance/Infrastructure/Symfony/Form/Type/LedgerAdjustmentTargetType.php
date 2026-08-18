<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form\Type;

use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Infrastructure\Symfony\Form\LedgerAdjustmentFormData;
use App\Payments\Domain\Model\Payee\PayeeSubjectDirectory;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<mixed> */
class LedgerAdjustmentTargetType extends AbstractType
{
    public function __construct(private PayeeSubjectDirectory $subjectDirectory) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => $this->choices(),
            'placeholder' => new TranslatableMessage('Seleccione un beneficiario'),
        ]);
    }

    /** @return array<string, array<string, string>|string> */
    private function choices(): array
    {
        return [
            (string) new TranslatableMessage('General') => [
                (string) new TranslatableMessage('Bioscore') => LedgerBeneficiary::Bioscore->value,
                (string) new TranslatableMessage('Sin asignar') => LedgerBeneficiary::Unassigned->value,
            ],
            (string) new TranslatableMessage('Cuentas') => $this->subjects(LedgerBeneficiary::Account, PayeeType::Account),
            (string) new TranslatableMessage('Proyectos') => $this->subjects(LedgerBeneficiary::Project, PayeeType::Project),
        ];
    }

    /** @return array<string, string> */
    private function subjects(LedgerBeneficiary $beneficiary, PayeeType $payeeType): array
    {
        $choices = [];

        foreach ($this->subjectDirectory->subjects($payeeType) as $subjectId => $label) {
            $choices[$label] = $beneficiary->value . LedgerAdjustmentFormData::TARGET_SEPARATOR . $subjectId;
        }

        return $choices;
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
