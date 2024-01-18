<?php

namespace App\Form;

use App\Entity\CompanyEvent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyEventType extends AbstractType
{
    const LABEL = 'label';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'date',
                DateType::class,
                [
                    self::LABEL => 'Fecha',
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd'
                ]
            )
            ->add(
                'description',
                null,
                [
                    self::LABEL => 'Descripción'
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CompanyEvent::class,
            'child' => true
        ]);
    }
}
