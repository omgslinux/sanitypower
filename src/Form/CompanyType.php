<?php

namespace App\Form;

use App\Entity\Company;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'fullname',
                null,
                [
                    'label' => 'Nombre largo'
                ]
            )
            ->add(
                'ShortName',
                null,
                [
                    'label' => 'Nombre corto'
                ]
            )
            ->add(
                'country',
                CountryType::class,
                [
                    'label' => 'País',
                    'choice_translation_locale' => 'es',
                    'data' => 'ES'
                ]
            )
            ->add(
                'active',
                null,
                [
                    'label' => 'Activa'
                ]
            )
            ->add(
                'notes',
                null,
                [
                    'label' => 'Notas'
                ]
            )
            ->add(
                'level',
                null,
                [
                    'label' => 'Nivel'
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Company::class,
        ]);
    }
}
