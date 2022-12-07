<?php

namespace App\Form;

use App\Entity\CompanyIncoming;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomingType extends AbstractType
{
    const LABEL = 'label';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'amount',
                null,
                [
                    self::LABEL => 'Cantidad',
                ]
            )
            ->add(
                'year',
                DateType::class,
                [
                    self::LABEL => 'Fecha',
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd'
                ]
            )
            ->add(
                'currency',
                null,
                [
                    self::LABEL => 'Moneda'
                ]
            )
        ;

        if (!$options['child']) {
            $builder
            ->add(
                'Company',
                null,
                [
                    self::LABEL => 'Empresa'
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CompanyIncoming::class,
            'child' => true
        ]);
    }
}
