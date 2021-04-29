<?php

namespace App\Form;

use App\Entity\StaffMembership;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StaffMembershipType extends AbstractType
{
    const LABEL = 'label';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'staffMember',
                null,
                [
                    self::LABEL => 'Miembro de Junta',
                ]
            )
            ->add(
                'title',
                null,
                [
                    self::LABEL => 'Cargo',
                ]
            )
            ->add(
                'datefrom',
                DateType::class,
                [
                    self::LABEL => 'Fecha desde',
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd',
                    'required' => false,
                ]
            )
            ->add(
                'dateto',
                DateType::class,
                [
                    self::LABEL => 'Fecha hasta',
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd',
                    'required' => false,
                ]
            )
        ;
        if (!$options['child']) {
            $builder
            ->add(
                'company',
                null,
                [
                    self::LABEL => 'Empresa'
                ]
            );
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
            'data_class' => StaffMembership::class,
            'child' => true,
            'batch' => false,
        ]);
    }
}
