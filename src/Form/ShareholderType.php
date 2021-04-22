<?php

namespace App\Form;

use App\Entity\Shareholder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShareholderType extends AbstractType
{
    const LABEL = 'label';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'holder',
                null,
                [
                    self::LABEL => 'Accionista'
                ]
            )
            ->add(
                'holderCategory',
                null,
                [
                    self::LABEL => 'Tipo'
                ]
            )
            ->add(
                'directOwnership',
                null,
                [
                    self::LABEL => '% directo'
                ]
            )
            ->add(
                'totalOwnership',
                null,
                [
                    self::LABEL => '% total'
                ]
            )
        ;
        if (!$options['child']) {
            $builder
            ->add('company');
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Shareholder::class,
            'child' => true,
        ]);
    }
}
