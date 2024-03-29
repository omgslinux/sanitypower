<?php

namespace App\Form;

use App\Entity\Company;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyType extends AbstractType
{
    const LABEL = 'label';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'fullname',
                null,
                [
                    self::LABEL => 'Nombre normalizado'
                ]
            )
            ->add(
                'realname',
                null,
                [
                    self::LABEL => 'Nombre real'
                ]
            )
            ->add(
                'ShortName',
                null,
                [
                    self::LABEL => 'Nombre corto'
                ]
            )
            ->add(
                'country',
                CountryType::class,
                [
                    self::LABEL => 'País',
                    'choice_translation_locale' => 'es',
                    //'data' => 'ES'
                ]
            )
            ->add(
                'active',
                null,
                [
                    self::LABEL => 'Activa'
                ]
            )
            ->add(
                'notes',
                null,
                [
                    self::LABEL => 'Notas'
                ]
            )
            ->add(
                'level',
                null,
                [
                    self::LABEL => 'Rol'
                ]
            )
            ->add(
                'category',
                null,
                [
                    self::LABEL => 'Tipo'
                ]
            )
            ->add('inList')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Company::class,
        ]);
    }
}
