<?php

namespace App\Form;

use App\Entity\Shareholder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShareholderType extends AbstractType
{
    const LABEL = 'label';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('holderCategory')
        ;
        if (!$options['child']) {
            $builder
            ->add('company');
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
        } else {
            if ($options['load_holder']) {
                $builder
                ->add(
                    'holder',
                    null,
                    [
                        self::LABEL => 'Accionista'
                    ]
                );
            }
            $builder
            ->add(
                'via',
                null,
                [
                    self::LABEL => 'Vía',
                    'required' => false,
                ]
            )
            ->add(
                'direct',
                null,
                [
                    self::LABEL => '% directo'
                ]
            )
            ->add(
                'total',
                null,
                [
                    self::LABEL => '% total'
                ]
            )
            ->add(
                'skip',
                null,
                [
                    self::LABEL => 'Omitir',
                ]
            )
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Shareholder::class,
            'child' => true,
            'batch' => false,
            'load_holder' => false,
        ]);
    }
}
