<?php

namespace App\Waste\Infrastructure\Symfony\Type;

use App\Waste\Domain\Model\Waste\Waste;
use Symfony\Component\Form\AbstractType;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Waste\WasteFilter;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use App\Waste\Domain\Model\Waste\WasteRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class WasteIdType extends AbstractType
{
    public function __construct(
        private WasteRepository $wasteRepository
    ) {

    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(
            new CallbackTransformer(
                function (?WasteId $wasteId): string {
                    return $wasteId ? (string) $wasteId : '';
                },
                function (?string $wasteId): WasteId|null {
                    return (null == $wasteId || empty($wasteId)) ? null : new WasteId($wasteId);
                }
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $wastes = $this->wasteRepository->findByFilter(
            new WasteFilter()
        );

        $resolver->setDefaults(array(
            'choices' => $this->getChoices($wastes)
        ));
    }
    
    public function getParent()
    {
        return ChoiceType::class;
    }

    private function getChoices($wastes)
    {
        if (null == $wastes || count($wastes) == 0) {
            return [];
        }

        $choices = [];

        foreach ($wastes as $waste) {
            $name = $waste->name();

            $i = 0;

            while (isset($choices[$name])) {
                $name = sprintf("%s (%d)", $waste->name(), ++$i);
            }

            $choices[$name] = (string) $waste->id();
        }

        return $choices;
    }
}