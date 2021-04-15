<?php

namespace App\Form;

use App\Entity\Subsidiary;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubsidiaryType extends AbstractType
{
    const LABEL = 'label';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'percent',
                null,
                [
                    self::LABEL => 'Porcentaje de participación'
                ]
            )
            ->add(
                'owned',
                null,
                [
                    self::LABEL => 'Participada'
                ]
            )
        ;
        if (!$options['child']) {
            $builder
            ->add('owner');
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Subsidiary::class,
            'child' => true,
        ]);
    }
}
