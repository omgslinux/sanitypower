<?php

namespace App\Form;

use App\Entity\CurrencyExchange;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurrencyExchangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'year',
                null,
                [
                    'label' => 'Fecha',
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd'
                ]
            )
            ->add(
                'amount',
                null,
                [
                    'label' => 'Cantidad'
                ]
            )
            ->add(
                'currency',
                null,
                [
                    'label' => 'Moneda'
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CurrencyExchange::class,
        ]);
    }
}
