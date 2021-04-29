<?php

namespace App\Form;

use App\Entity\Subsidiary;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
        if ($options['batch']) {
            $builder
            ->add(
                'batch',
                TextareaType::class,
                [
                    self::LABEL => 'Carga masiva',
                    'mapped' => false,
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Subsidiary::class,
            'child' => true,
            'batch' => false,
        ]);
    }
}
