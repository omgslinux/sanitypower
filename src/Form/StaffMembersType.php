<?php

namespace App\Form;

use App\Entity\StaffMembers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StaffMembersType extends AbstractType
{
    const LABEL = 'label';
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'surname',
                null,
                [
                    self::LABEL => 'Apellidos',
                ]
            )
            ->add(
                'name',
                null,
                [
                    self::LABEL => 'Nombre',
                ]
            )
            ->add(
                'notes',
                null,
                [
                    self::LABEL => 'Notas',
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StaffMembers::class,
        ]);
    }
}
