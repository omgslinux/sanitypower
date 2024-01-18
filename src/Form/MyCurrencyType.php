<?php

namespace App\Form;

use App\Entity\Currency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MyCurrencyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'name',
                null,
                [
                    'label' => 'Nombre'
                ]
            )
            ->add(
                'code',
                CurrencyType::class,
                [
                    'label' => 'Código',
                    'choice_translation_locale' => 'es',
                ]
            )
            ->add(
                'symbol',
                null,
                [
                    'label' => 'Símbolo'
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Currency::class,
        ]);
    }
}
